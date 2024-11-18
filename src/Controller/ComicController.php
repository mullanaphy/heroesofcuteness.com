<?php

namespace App\Controller;

use App\Entity\Comic;
use App\Entity\Panel;
use App\Repository\ComicRepository;
use App\Service\MetaData;
use Exception;
use Michelf\Markdown;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ComicController extends AbstractController
{
    #[Route('/comic', name: 'comic_archive')]
    public function archive(Request $request, ComicRepository $repository): Response
    {
        return $this->render('comic/archive.html.twig', [
            'page' => [
                'url' => 'archive',
                'title' => 'Comic Archive',
                'parameters' => [],
            ],
            ...MetaData::paginator(
                $repository->all(),
                $request->query->getInt('pageId'),
                $request->query->getInt('limit'),
            )]);
    }

    #[Route('/comic/{id}', name: 'comic_item')]
    public function item(int $id, Request $request, ComicRepository $repository): Response
    {
        /* @var Comic $comic */
        $comic = $repository->findOneBy(['id' => $id]);

        $metaImage = null;
        $panels = [];
        foreach ($comic->getPanels() as $panel) {
            /* @var Panel $panel */
            if ($metaImage === null) {
                $metaImage = $request->getSchemeAndHttpHost() . $panel->getPath();
            }
            $panels[] = $panel->toArray();
        }

        $content = $comic->getContent();
        if (null !== $content) {
            if (!$comic->isRaw()) {
                $content = Markdown::defaultTransform($content);
            }
        }

        $canonicalUrl = $this->generateUrl('comic_item', ['id' => $comic->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
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
            'image' => $metaImage,
            'datePublished' => $comic->getCreated()->format('Y-m-d\TH:i:s\Z'),
            'dateModified' => $comic->getUpdated()->format('Y-m-d\TH:i:s\Z')
        ];

        $nextComic = $repository->findNextComic($comic);
        $previousComic = $repository->findPreviousComic($comic);

        return $this->render('comic/item.html.twig', [
            'comic' => $comic,
            'canonical_url' => $canonicalUrl,
            'content' => $content,
            'panels' => $panels,
            'meta' => $meta,
            'next_comic' => $nextComic ? $nextComic->getId() : null,
            'previous_comic' => $previousComic ? $previousComic->getId() : null
        ]);
    }
}
