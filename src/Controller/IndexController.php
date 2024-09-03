<?php

namespace App\Controller;

use App\Entity\Comic;
use App\Entity\Panel;
use App\Repository\ComicRepository;
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
        return $this->item(1, $repository);
    }

    #[Route('/comic', name: 'archive')]
    public function archive(Request $request, ComicRepository $repository): Response
    {
        $count = $repository->count();
        $pages = intval(ceil($count / self::LIMIT));

        $parameters = [];
        $pageId = $request->query->has('pageId')
            ? intval($request->query->get('pageId'))
            : 1;
        if (!is_numeric($pageId) || $pageId < 1) {
            $pageId = 1;
        } else if ($pageId > $pages) {
            $pageId = $pages;
        }

        if ($pageId > 1) {
            $parameters['pageId'] = $request->query->get('pageId');
        }

        $start = ($pageId * self::LIMIT) - self::LIMIT;
        $comics = $repository->findByRange($start, self::LIMIT);

        return $this->render('index.html.twig', [
            'comics' => $comics,
            'count' => $count,
            'canonical_url' => $this->generateUrl('archive', $parameters)
        ]);
    }

    #[Route('/comic/{id}', name: 'comic')]
    public function item(int $id, ComicRepository $repository): Response
    {
        try {
            /* @var Comic $comic */
            $comic = $repository->findOneBy(['id' => $id]);

            $metaImage = null;
            $panels = [];
            foreach ($comic->getPanels() as $panel) {
                if ($metaImage === null) {
                    $metaImage = $panel->getSource();
                }
                /* @var Panel $panel */
                $panels[] = $panel->toArray();
            }

            $content = $comic->getContent();
            if (null !== $content) {
                if (!$comic->isRaw()) {
                    $content = Markdown::defaultTransform($content);
                }
            }

            $canonicalUrl = $this->generateUrl('comic', ['id' => $comic->getId()]);
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
                'datePublished' => $comic->getCreated(),
                'dateModified' => $comic->getUpdated()
            ];

            $nextComic = $repository->findNextComic($comic);
            $previousComic = $repository->findPreviousComic($comic);

            return $this->render('comic/item.html.twig', [
                'comic' => $comic,
                'canonical_url' => $canonicalUrl,
                'content' => $content,
                'panels' => $panels,
                'comic_meta' => $meta,
                'next_comic' => $nextComic ? $nextComic->getId() : null,
                'previous_comic' => $previousComic ? $previousComic->getId() : null
            ]);
        } catch (Exception|NotFoundExceptionInterface|ContainerExceptionInterface) {
            return $this->redirectToRoute('index');
        }
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, ComicRepository $repository): Response
    {
        return $this->render('comic/index.html.twig', [
            'collection' => $repository->search($request->query->get('q'))
        ]);
    }
}
