<?php

namespace App\Serializers\Converters;

use App\Services\DateFormatterService;
use DateTimeInterface;
use Framework\Serializer\Converter\ISerializerDateTimeConverter;

class DateTimeConverter implements ISerializerDateTimeConverter
{
    public DateFormatterService $dateFormatterService;

    public function convert(DateTimeInterface $dateTime, string $format): string
    {
        return $this->dateFormatterService->format($dateTime, $format);
    }
}