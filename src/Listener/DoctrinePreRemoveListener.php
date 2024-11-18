<?php

namespace App\Listener;

use App\Entity\Character;
use App\Entity\Comic;
use App\Repository\SearchRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::preRemove)]
readonly class DoctrinePreRemoveListener
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private SearchRepository       $searchRepository,
    )
    {

    }

    /**
     * @throws InvalidArgumentException
     */
    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof Character) {
            $this->searchRepository->remove('Character', $entity->getId());
        } else if ($entity instanceof Comic) {
            $this->searchRepository->remove('Comic', $entity->getId());
        }

        $this->cache->invalidateTags(['comics']);
    }
}
