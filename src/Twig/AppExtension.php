<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('relative_path', [$this, 'relativePath']),
        ];
    }

    public function relativePath(string $path): string
    {
        return ltrim($path, "/");
    }
}