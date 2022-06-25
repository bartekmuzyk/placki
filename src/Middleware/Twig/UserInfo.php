<?php

namespace App\Middleware\Twig;

use App\App;
use App\Services\AccountService;
use Framework\Exception\NoSuchServiceException;
use Framework\Middleware\TwigMiddlewareInterface;
use Twig\Environment;

class UserInfo implements TwigMiddlewareInterface
{
    /**
     * @throws NoSuchServiceException
     */
    public function run(App $app, Environment $twigEnvironment): void
    {
        /** @var AccountService $accountService */
        $accountService = $app->getService(AccountService::class);

        if (!$accountService->isLoggedIn()) {
            return;
        }

        $twigEnvironment->addGlobal('self', $accountService->currentLoggedInUser);
    }
}