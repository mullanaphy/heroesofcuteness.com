<?php

namespace App\Listener;

use App\Entity\Character;
use App\Entity\Comic;
use App\Entity\Hidden;
use App\Entity\Panel;
use App\Entity\Search;
use App\Repository\SearchRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Michelf\Markdown;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
#[AsDoctrineListener(event: Events::preRemove)]
class SearchListener
{
    private const INSERT = 'INSERT INTO search (entity, entity_id, content, created, updated) VALUES (:entity, :entityId, :content, :created, :updated)';
    private const UPDATE = 'UPDATE search SET content = :content WHERE entity = :entity AND entity_id = :entityId';

    private array $toSave = [];

    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly SearchRepository       $searchRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {

    }

    /**
     * We're going to first collect any entity changes that would involve search. Once stored we'll then process them
     * accordingly in postFlush.
     *
     * @param OnFlushEventArgs $eventArgs
     * @return void
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $manager = $eventArgs->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();

        foreach ([...$unitOfWork->getScheduledEntityUpdates(), ...$unitOfWork->getScheduledEntityInsertions()] as $entity) {
            if ($entity instanceof Comic) {
                $this->toSave[] = $this->pairSearchAndEntity($entity);
            } else if ($entity instanceof Panel || $entity instanceof Hidden) {
                $this->toSave[] = $this->pairSearchAndEntity($entity->getComic());
            } else if ($entity instanceof Character) {
                $this->toSave[] = $this->pairSearchAndEntity($entity);
            }
        }
    }

    /**
     * Now that we have search data to save via postFlush, we'll now save them accordingly.
     *
     * @param PostFlushEventArgs $eventArgs
     * @return void
     * @throws InvalidArgumentException
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        if (!count($this->toSave)) {
            return;
        }

        foreach ($this->toSave as $pair) {
            /* @var Search $search */
            /* @var Comic|Character $entity */
            list($search, $entity) = $pair;

            $this->entityManager->createNativeQuery($search->getEntityId() ? self::UPDATE : self::INSERT, new ResultSetMapping())
                ->setParameters([
                    'entity' => $search->getEntity(),
                    'entityId' => $entity->getId(),
                    'content' => $search->getContent(),
                    'created' => $search->getCreated(),
                    'updated' => $search->getUpdated(),
                ])
                ->execute();
        }

        $this->toSave = [];
        $this->cache->invalidateTags(['comics']);
    }

    /**
     * If an entity is removed that would have search, we'll need to remove that now as well.
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     * @throws InvalidArgumentException
     */
    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comic) {
            $this->searchRepository->remove('Comic', $entity->getId());
        } else if ($entity instanceof Panel || $entity instanceof Hidden) {
            $this->searchRepository->remove('Comic', $entity->getComic()->getId());
        } else if ($entity instanceof Character) {
            $this->searchRepository->remove('Character', $entity->getId());
        } else {
            return;
        }

        $this->cache->invalidateTags(['comics']);
    }

    private function pairSearchAndEntity(Character|Comic $entity): array
    {
        $fragments = explode('\\', $entity::class);
        $entityName = array_pop($fragments);

        if ($entity->getId()) {
            $search = $this->searchRepository->findOneBy(['entity' => $entityName, 'entity_id' => $entity->getId()]);
            $search->refreshUpdated();
        } else {
            $search = new Search;
            $search->refreshCreated();
            $search->setEntity($entityName);
        }
        $search->setContent(self::cleanContent($entity instanceof Comic
            ? $this->getComicContent($entity)
            : $this->getCharacterContent($entity)
        ));

        return [$search, $entity];
    }

    private function getComicContent(Comic $comic): array
    {
        $comicContent = $comic->getContent();
        if ($comicContent && !$comic->isRaw()) {
            $comicContent = Markdown::defaultTransform($comicContent);
        }
        $content = [
            $comic->getTitle(),
            $comicContent,
            $comic->getAuthor()->getUsername(),
            $comic->getDescription(),
        ];

        foreach ($comic->getPanels() as $panel) {
            $content[] = $panel->getDialogue();
            $content[] = $panel->getAlt();
            $content[] = $panel->getTitle();
        }

        $hidden = $comic->getHidden();
        if (null !== $hidden) {
            $content[] = $comic->getHidden()->getAlt();
        }

        return $content;
    }

    private function getCharacterContent(Character $character): array
    {
        $content = [
            $character->getName(),
            $character->getNickname(),
        ];

        $biography = $character->getBiography();
        if ($biography && !$character->isRaw()) {
            $biography = Markdown::defaultTransform($biography);
        }
        $content[] = $biography;

        return $content;
    }

    private static function cleanContent(array $content): string
    {
        return strtolower(
            preg_replace('/[^A-Za-z0-9 ]/', '',
                preg_replace('/[\t\r\n ]+/', ' ',
                    strip_tags(
                        implode(' ', array_filter($content, function ($item) {
                                return is_string($item) && $item;
                            })
                        )
                    )
                )
            )
        );
    }
}
