<?php

namespace App\Middleware\Twig;

use App\App;
use Framework\Middleware\TwigMiddlewareInterface;
use Twig\Environment;
use Twig\TwigFilter;

class Breakify implements TwigMiddlewareInterface
{
    public function run(App $app, Environment $twigEnvironment): void
    {
        $twigEnvironment->addFilter(new TwigFilter(
            'breakify',
            fn(string $source) => str_replace("\n", '<br/>', $source),
            [
                'pre_escape' => 'html',
                'is_safe' => ['html']
            ]
        ));
    }
}