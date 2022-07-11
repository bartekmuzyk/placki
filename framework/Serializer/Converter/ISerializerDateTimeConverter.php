<?php

namespace Framework\Serializer\Converter;

use DateTimeInterface;

interface ISerializerDateTimeConverter
{
    public function convert(DateTimeInterface $dateTime, string $format): mixed;
}