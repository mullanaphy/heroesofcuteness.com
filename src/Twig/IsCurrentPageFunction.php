<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsCurrentPageFunction extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isCurrentPage', [$this, 'isCurrentPage']),
        ];
    }

    public function isCurrentPage(string $page, Request $request): bool
    {
        return $request->getPathInfo() === $page || $request->getUri() === $page;
    }
}
