<?php

namespace App\Controller;

use App\Entity\Search;
use App\Repository\SearchRepository;
use App\Service\MetaData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, SearchRepository $repository): Response
    {
        $search = strip_tags($request->query->get('q'));
        if (!$search) {
            return $this->redirectToRoute('index');
        }

        $data = MetaData::paginator(
            $repository->findByQuery($search),
            $request->query->getInt('pageId'),
            $request->query->getInt('limit'),
        );

        $collection = [];
        foreach ($data['collection'] as $item) {
            /* @var Search $item */
            $collection[] = $item->getComic();
        }
        $data['collection'] = $collection;

        return $this->render('comic/index.html.twig', [
            'page' => [
                'url' => 'search',
                'title' => 'Search <em>(' . $search . ')</em>',
                'parameters' => [
                    'q' => $search
                ],
            ],
            ...$data
        ]);
    }
}
