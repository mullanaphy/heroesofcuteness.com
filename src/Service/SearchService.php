<?php

namespace App\Service;

use App\Entity\Character;
use App\Entity\Comic;
use App\Entity\Search;
use App\Repository\CharacterRepository;
use App\Repository\ComicRepository;
use App\Repository\SearchRepository;

readonly class SearchService
{
    public function __construct(
        private SearchRepository    $searchRepository,
        private ComicRepository     $comicRepository,
        private CharacterRepository $characterRepository,
    )
    {

    }

    public function search(?string $search, ?int $pageId, ?int $limit): ?array
    {
        $search = strip_tags($search);
        $data = MetaDataService::paginator(
            $this->searchRepository->findByQuery($search),
            $pageId,
            $limit,
        );

        $collection = [];
        foreach ($data['collection'] as $result) {
            $collection[] = $this->getEntityData($result[0], $result['score']);
        }
        $data['collection'] = $collection;

        return [
            'page' => [
                'url' => 'search',
                'title' => 'Search <em>(' . $search . ')</em>',
                'parameters' => [
                    'q' => $search,
                ],
            ],
            ...$data,
        ];
    }

    private function getEntityData(Search $search, ?float $score): array
    {
        return ['score' => $score,
            ...match ($search->getEntity()) {
                'Character' => $this->normalizeCharacter($search->getEntityId()),
                'Comic' => $this->normalizeComic($search->getEntityId()),
                default => [],
            }];
    }

    private function normalizeCharacter(int $id): ?array
    {
        /* @var Character $character */
        $character = $this->characterRepository->findOneBy(['id' => $id]);

        return [
            'type' => 'character',
            'id' => $character->getId(),
            'image' => $character->getThumbnailPath(),
            'title' => $character->getName(),
            'description' => $character->getDescription(),
        ];
    }

    private function normalizeComic(int $id): ?array
    {
        /* @var Comic $comic */
        $comic = $this->comicRepository->findOneBy(['id' => $id]);

        return [
            'type' => 'comic',
            'id' => $comic->getId(),
            'image' => $comic->getThumbnailPath(),
            'title' => $comic->getTitle(),
            'description' => $comic->getDescription(),
        ];
    }
}
