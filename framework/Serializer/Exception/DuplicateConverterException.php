<?php

namespace Framework\Serializer\Exception;

class DuplicateConverterException extends SerializerException
{
    public function __construct(string $existingConverterClassName, string $duplicateConverterClassName)
    {
        parent::__construct("$duplicateConverterClassName implements the same converter interface as the already assigned converter $duplicateConverterClassName.");
    }
}