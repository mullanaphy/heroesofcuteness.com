<?php

namespace App\Service;

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
}
