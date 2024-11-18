<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\Comic;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MetaData
{
    public static function paginator(Paginator $paginator = null, ?int $pageId = 1, ?int $limit = 8, $neighborCount = 2): array
    {
        if (!$pageId || $pageId < 1) {
            $pageId = 1;
        }
        if (!$limit) {
            $limit = 8;
        } else if ($limit > 32) {
            $limit = 32;
        }

        $total = count($paginator);
        $pages = ceil($total / $limit);
        if ($pages < 1) {
            $pages = 1;
        }
        if ($pageId > $pages) {
            $pageId = $pages;
        }

        $paginator->getQuery()
            ->setFirstResult(($pageId * $limit) - $limit)
            ->setMaxResults($limit);

        $neighbors = [];
        for ($i = max(1, $pageId - $neighborCount); $i <= $pageId; ++$i) {
            $neighbors[] = $i;
        }
        for ($i = $pageId + 1, $max = min($pageId + $neighborCount, $pages); $i <= $max; ++$i) {
            $neighbors[] = $i;
        }

        return [
            'collection' => $paginator,
            'start' => ($pageId * $limit) - $limit,
            'pageId' => $pageId,
            'pages' => $pages,
            'limit' => $limit,
            'total' => $total,
            'neighbors' => $neighbors
        ];
    }

    public static function ldJson(Character|Comic $entity, string $canonicalUrl, ?string $image): array
    {
        $metaData = [
            '@context' => 'http://schema.org',
            '@type' => 'Article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl
            ],
            'author' => [
                [
                    '@type' => 'Person',
                    'url' => 'https://john.mu',
                    'name' => 'John Mullanaphy'
                ],
                [
                    '@type' => 'Person',
                    'url' => 'https://initials.kim',
                    'name' => 'Keira Mullanaphy'
                ],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Heroes of Cuteness',
            ],
            'image' => $image,
            'datePublished' => $entity->getCreated()->format('Y-m-d\TH:i:s\Z'),
            'dateModified' => $entity->getUpdated()->format('Y-m-d\TH:i:s\Z')
        ];

        return $metaData;
    }
}
