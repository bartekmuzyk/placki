<?php

namespace App\Middleware\Twig;

use App\App;
use Framework\Middleware\TwigMiddlewareInterface;
use Twig\Environment;

class IsElectronApp implements TwigMiddlewareInterface
{
    public function run(App $app, Environment $twigEnvironment): void
    {
        $req = $app->getRequest();
        $isElectronApp = str_contains($req->headers['user-agent'], 'placki-desktop');

        $twigEnvironment->addGlobal('IS_ELECTRON_APP', $isElectronApp);
    }
}