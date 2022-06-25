<?php

namespace Framework\Middleware;

use App\App;
use Twig\Environment;

interface TwigMiddlewareInterface
{
    public function run(App $app, Environment $twigEnvironment): void;
}