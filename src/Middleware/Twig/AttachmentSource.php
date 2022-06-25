<?php

namespace App\Middleware\Twig;

use App\App;
use App\Entities\Attachment;
use App\Services\AttachmentService;
use App\Services\CDNService;
use Framework\Exception\NoSuchServiceException;
use Framework\Middleware\TwigMiddlewareInterface;
use Twig\Environment;
use Twig\TwigFilter;

class AttachmentSource implements TwigMiddlewareInterface
{
    /**
     * @param App $app
     * @param Environment $twigEnvironment
     * @return void
     * @throws NoSuchServiceException
     */
    public function run(App $app, Environment $twigEnvironment): void
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = $app->getService(AttachmentService::class);

        $twigEnvironment->addFilter(new TwigFilter(
            'att_src',
            fn(Attachment $attachment) => $attachmentService->getAttachmentFilePath($attachment)
        ));
    }
}