<?php

namespace App\Listener;

use App\Entity\Comic;
use App\Entity\Hidden;
use App\Entity\Panel;
use App\Entity\Search;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class DoctrineListener
{
    public function __construct(private readonly TagAwareCacheInterface $cache)
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
        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            if ($entity instanceof Comic) {
                $comics[$entity->getId()] = $entity;
            } else if ($entity instanceof Panel || $entity instanceof Hidden && !array_key_exists($entity->getComic()->getId(), $comics)) {
                $comics[$entity->getComic()->getId()] = $entity->getComic();
            }
        }

        if (!count($comics)) {
            $this->cache->invalidateTags(['comics']);
            return;
        }

        foreach ($comics as $comic) {
            $content = [
                $comic->getTitle(),
                $comic->getAuthor()->getUsername()
            ];
            foreach ($comic->getPanels() as $panel) {
                $content[] = $panel->getAlt();
            }

            $hidden = $comic->getHidden();
            if (null !== $hidden) {
                $content[] = $comic->getHidden()->getAlt();
            }

            $search = $comic->getSearch() ?? new Search;
            $search->setComic($comic);
            $search->setContent(implode(' ', $content));
            $comic->setSearch($search);
            $manager->persist($comic);
            $unitOfWork->computeChangeSet($manager->getClassMetadata(Comic::class), $comic);
            $unitOfWork->computeChangeSet($manager->getClassMetadata(Search::class), $search);
        }

        $this->cache->invalidateTags(['comics']);
    }
}
