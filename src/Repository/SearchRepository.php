<?php

namespace App\Repository;

use App\Config;
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
        $queryBuilder = $this->createQueryBuilder('c')
            ->setParameter('q', implode('', ['%', strtolower($get), '%']));

        if (Config::USE_FULLTEXT) {
            $queryBuilder
                ->addSelect('MATCH(c.content) AGAINST (:q IN BOOLEAN MODE) AS score')
                ->andWhere('MATCH(c.content) AGAINST (:q IN BOOLEAN MODE) > 0.2');
        } else {
            $queryBuilder
                ->addSelect('1 AS score')
                ->andWhere('c.content LIKE :q');
        }

        return new Paginator(
            $queryBuilder,
            fetchJoinCollection: true
        );
    }

    public function remove(string $entity, int $id): void
    {
        $this->createQueryBuilder('s')
            ->delete(Search::class, 's')
            ->where('s.entity = :entity AND s.entity_id = :entityId')
            ->setParameter('entity', $entity)
            ->setParameter('entityId', $id)
            ->getQuery()
            ->execute();
    }
}
