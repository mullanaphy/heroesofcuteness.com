<?php

namespace App\Controller;

use App\Entity\Search;
use App\Repository\ComicRepository;
use App\Service\MetaData;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ComicRepository $repository): Response
    {
        return $this->forward(implode('::', [ComicController::class, 'item']), ['id' => $repository->count()]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, SearchService $service): Response
    {
        $q = $request->query->get('q');
        if (!$q) {
            return $this->redirectToRoute('index');
        }

        return $this->render('search.html.twig', $service->search($q, $request->query->get('pageId'), $request->query->get('limit')));
    }
}
