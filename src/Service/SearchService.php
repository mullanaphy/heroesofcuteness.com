<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\Comic;
use App\Entity\Search;
use App\Repository\CharacterRepository;
use App\Repository\ComicRepository;
use App\Repository\SearchRepository;

class SearchService
{
    public function __construct(
        private readonly SearchRepository    $searchRepository,
        private readonly ComicRepository     $comicRepository,
        private readonly CharacterRepository $characterRepository,
    )
    {

    }

    public function search(?string $search, ?int $pageId, ?int $limit): ?array
    {
        if (!$search) {
            return null;
        }

        $data = MetaData::paginator(
            $this->searchRepository->findByQuery($search),
            $pageId,
            $limit,
        );

        $collection = [];
        foreach ($data['collection'] as $item) {
            /* @var Search $item */
            $collection[] = $this->getEntityData($item);
        }
        $data['collection'] = $collection;

        return [
            'page' => [
                'url' => 'search',
                'title' => 'Search <em>(' . $search . ')</em>',
                'parameters' => [
                    'q' => $search
                ],
            ],
            ...$data
        ];
    }

    private function getEntityData(Search $search): ?array
    {
        return match ($search->getEntity()) {
            'Character' => $this->normalizeCharacter($search->getEntityId()),
            'Comic' => $this->normalizeComic($search->getEntityId()),
            default => null,
        };
    }

    private function normalizeCharacter(int $id): ?array
    {
        /* @var Character $character */
        $character = $this->characterRepository->findOneBy(['id' => $id]);

        return [
            'type' => 'character',
            'id' => $character->getId(),
            'image' => $character->getPath(),
            'title' => $character->getName(),
            'content' => $character->getBiography(),
        ];
    }

    private function normalizeComic(int $id): ?array
    {
        /* @var Comic $comic */
        $comic = $this->comicRepository->findOneBy(['id' => $id]);

        $panels = $comic->getPanels();
        $image = null;
        if (count($panels)) {
            $image = $panels[0]->getPath();
        }

        return [
            'type' => 'comic',
            'id' => $comic->getId(),
            'image' => $image,
            'title' => $comic->getTitle(),
            'content' => $comic->getContent(),
        ];
    }
}
