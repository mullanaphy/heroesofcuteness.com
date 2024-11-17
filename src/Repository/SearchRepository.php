<?php

namespace App\Repository;

use App\Entity\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Search>
 */
class SearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Search::class);
    }

    public function findByQuery(string $get): Paginator
    {
        return new Paginator(
            $this->createQueryBuilder('c')
                ->andWhere('c.content LIKE :get')
                ->setParameter('get', implode('', ['%', strtolower($get), '%'])),
            fetchJoinCollection: true
        );
    }
}
