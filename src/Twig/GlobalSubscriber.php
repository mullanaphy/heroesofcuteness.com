<?php

namespace App\Twig;

use App\Entity\Comic;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Environment;
use Twig\TwigFunction;

class GlobalSubscriber implements EventSubscriberInterface
{
    const EXPIRATION = 86400 * 7;

    public function __construct(
        private readonly Environment            $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly TagAwareCacheInterface $cache
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function injectGlobalVariables(): void
    {
        $this->twig->addGlobal('header_navigation', $this->cache->get('header_navigation', function (ItemInterface $item): array {
            $item->expiresAfter(self::EXPIRATION);
            $item->tag('comic');
            $comics = [
                'current' => null,
                'first' => null,
            ];
            $comicRepository = $this->entityManager->getRepository(Comic::class);

            $currentComic = $comicRepository->findOneBy([], ['id' => 'DESC']);
            if ($currentComic) {
                $comics['current'] = $currentComic->getId();
            }

            $firstComic = $comicRepository->findOneBy([], ['id' => 'ASC']);
            if ($firstComic && $comics['current'] !== $firstComic->getId()) {
                $comics['first'] = $firstComic->getId();
            }

            return $comics;
        }));
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'injectGlobalVariables'];
    }
}
