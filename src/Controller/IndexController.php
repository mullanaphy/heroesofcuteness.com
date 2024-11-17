<?php

namespace App\Controller;

use App\Entity\Comic;
use App\Entity\Panel;
use App\Repository\ComicRepository;
use App\Repository\SearchRepository;
use Exception;
use Michelf\Markdown;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    const LIMIT = 10;

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
}
