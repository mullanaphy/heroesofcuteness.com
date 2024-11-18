<?php

namespace App\Controller;

use App\Entity\Character;
use App\Repository\CharacterRepository;
use App\Service\MetaDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CharacterController extends AbstractController
{
    #[Route('/character', name: 'character_archive')]
    public function archive(Request $request, CharacterRepository $repository): Response
    {
        return $this->render('character/archive.html.twig', [
            'page' => [
                'url' => 'archive',
                'title' => 'Character Archive',
                'parameters' => [],
            ],
            ...MetaDataService::paginator(
                $repository->all(),
                $request->query->getInt('pageId'),
                $request->query->getInt('limit'),
            )]);
    }

    #[Route('/character/{id}', name: 'character_item')]
    public function item(int $id, Request $request, CharacterRepository $repository): Response
    {
        /* @var Character $character */
        $character = $repository->findOneBy(['id' => $id]);

        $canonicalUrl = $this->generateUrl('character_item', ['id' => $character->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return $this->render('character/item.html.twig', [
            'character' => $character,
            'canonical_url' => $canonicalUrl,
            'meta' => MetaDataService::ldJson($character, $canonicalUrl),
        ]);
    }
}
