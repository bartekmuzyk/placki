<?php

namespace App\Middleware\Twig;

use App\App;
use App\Services\DateFormatterService;
use DateTime;
use Framework\Exception\NoSuchServiceException;
use Framework\Middleware\TwigMiddlewareInterface;
use Twig\Environment;
use Twig\TwigFilter;

class DateFormatter implements TwigMiddlewareInterface
{
    /**
     * @param App $app
     * @param Environment $twigEnvironment
     * @return void
     * @throws NoSuchServiceException
     */
    public function run(App $app, Environment $twigEnvironment): void
    {
        /** @var DateFormatterService $dateFormatterService */
        $dateFormatterService = $app->getService(DateFormatterService::class);

        $twigEnvironment->addFilter(new TwigFilter('format_date', function (DateTime $dateTime, string $format) use ($dateFormatterService) {
            return $dateFormatterService->format($dateTime, $format);
        }));
        $twigEnvironment->addFilter(new TwigFilter('format_date_human', function (DateTime $dateTime) use ($dateFormatterService) {
            return $dateFormatterService->format($dateTime, DateFormatterService::FORMAT_DATE) . ' o ' . $dateTime->format('G:i');
        }));
        $twigEnvironment->addFilter(new TwigFilter('format_date_hour', function (DateTime $dateTime) use ($dateFormatterService) {
            return $dateTime->format('G:i');
        }));
    }
}