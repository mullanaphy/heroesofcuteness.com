<?php

namespace App\Controller;

use App\Entity\Character;
use App\Repository\CharacterRepository;
use App\Service\MetaData;
use Michelf\Markdown;
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
            ...MetaData::paginator(
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
        $meta = [
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
            'image' => $request->getSchemeAndHttpHost() . $character->getPath(),
            'datePublished' => $character->getCreated()->format('Y-m-d\TH:i:s\Z'),
            'dateModified' => $character->getUpdated()->format('Y-m-d\TH:i:s\Z')
        ];

        $biography = $character->getBiography();
        if (null !== $biography) {
            if (!$character->isRaw()) {
                $biography = Markdown::defaultTransform($biography);
            }
        }

        return $this->render('character/item.html.twig', [
            'character' => $character,
            'biography' => $biography,
            'canonical_url' => $canonicalUrl,
            'meta' => $meta
        ]);
    }
}
