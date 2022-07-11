<?php

namespace Framework\Serializer\Exception;

class InvalidConverterException extends SerializerException
{
    public function __construct(string $className)
    {
        parent::__construct("Class $className cannot be used as a converter because it does not implement a known converter interface.");
    }
}