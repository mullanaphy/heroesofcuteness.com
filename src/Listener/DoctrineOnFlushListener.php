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
use Michelf\Markdown;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class DoctrineOnFlushListener
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

    /**
     * @throws InvalidArgumentException
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        foreach ($this->toSave as $pair) {
            /* @var Search $search */
            $search = $pair[0];
            /* @var Comic|Character $entity */
            $entity = $pair[1];

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
}
