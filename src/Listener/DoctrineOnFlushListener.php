<?php

namespace App\Listener;

use App\Entity\Character;
use App\Entity\Comic;
use App\Entity\Hidden;
use App\Entity\Panel;
use App\Entity\Search;
use App\Repository\SearchRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Michelf\Markdown;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
readonly class DoctrineOnFlushListener
{
    public function __construct(
        private TagAwareCacheInterface $cache
    )
    {

    }

    /**
     * @throws InvalidArgumentException
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $manager = $eventArgs->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();

        $comics = [];
        $characters = [];
        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            if ($entity instanceof Comic) {
                $comics[$entity->getId()] = $entity;
            } else if ($entity instanceof Panel || $entity instanceof Hidden && !array_key_exists($entity->getComic()->getId(), $comics)) {
                $comics[$entity->getComic()->getId()] = $entity->getComic();
            } else if ($entity instanceof Character) {
                $characters[$entity->getId()] = $entity;
            }
        }

        $repository = $manager->getRepository(Search::class);

        if (count($comics)) {
            $this->processComics($comics, $manager, $unitOfWork, $repository);
        }

        if (count($characters)) {
            $this->processCharacters($characters, $manager, $unitOfWork, $repository);
        }

        $this->cache->invalidateTags(['comics']);
    }

    private function processComics(array $comics, ObjectManager $manager, UnitOfWork $unitOfWork, SearchRepository $repository): void
    {
        foreach ($comics as $comic) {
            /* @var Comic $comic */
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

            $search = $this->findSearch('Comic', $comic->getId(), $repository);
            $search->setContent(self::cleanContent($content));
            $manager->persist($search);

            $unitOfWork->computeChangeSet($manager->getClassMetadata(Comic::class), $comic);
            $unitOfWork->computeChangeSet($manager->getClassMetadata(Search::class), $search);
        }
    }

    private function processCharacters(array $characters, ObjectManager $manager, UnitOfWork $unitOfWork, SearchRepository $repository): void
    {
        foreach ($characters as $character) {
            /* @var Character $character */
            $content = [
                $character->getName(),
                $character->getNickname(),
            ];

            $biography = $character->getBiography();
            if ($biography && !$character->isRaw()) {
                $biography = Markdown::defaultTransform($biography);
            }
            $content[] = $biography;

            $search = $this->findSearch('Character', $character->getId(), $repository);
            $search->setContent(self::cleanContent($content));
            $manager->persist($search);

            $unitOfWork->computeChangeSet($manager->getClassMetadata(Character::class), $character);
            $unitOfWork->computeChangeSet($manager->getClassMetadata(Search::class), $search);
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

    private function findSearch(string $entity, int $entityId, SearchRepository $repository): ?Search
    {
        $search = $repository->findOneBy(['entity' => $entity, 'entity_id' => $entityId]);
        if ($search === null) {
            $search = new Search;
            $search->refreshCreated();
        } else {
            $search->refreshUpdated();
        }

        $search->setEntity($entity);
        $search->setEntityId($entityId);
        return $search;
    }
}
