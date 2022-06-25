<?php

namespace App\Services;

use App\App;
use DateTimeInterface;
use Framework\Service\Service;
use IntlDateFormatter;

class DateFormatterService extends Service
{
    public const FORMAT_DATE = 'd MMMM YYYY';
    public const FORMAT_DATE_AND_TIME = self::FORMAT_DATE . ' (H:mm)';
    public const FORMAT_HUMAN = self::FORMAT_DATE . ' \'o\' H:mm';

    private IntlDateFormatter $formatter;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->formatter = new IntlDateFormatter('pl_PL');
    }

    public function format(DateTimeInterface $dateTime, string $format): string
    {
        $this->formatter->setPattern($format);
        return $this->formatter->format($dateTime);
    }
}