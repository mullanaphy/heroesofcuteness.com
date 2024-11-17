<?php

namespace App\Repository;

use App\Entity\Comic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comic>
 */
class ComicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comic::class);
    }

    public function findNextComic(Comic $comic)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id > :id')
            ->setParameter('id', $comic->getId())
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPreviousComic(Comic $comic)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id < :id')
            ->setParameter('id', $comic->getId())
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function all(): Paginator
    {
        return new Paginator(
            $this->createQueryBuilder('c')
                ->orderBy('c.id', 'ASC'),
            fetchJoinCollection: true
        );
    }
}
