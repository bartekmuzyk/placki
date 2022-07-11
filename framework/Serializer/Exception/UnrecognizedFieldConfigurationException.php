<?php

namespace Framework\Serializer\Exception;

use Framework\Utils\Utils;

class UnrecognizedFieldConfigurationException extends SerializerException
{
    public function __construct($key, $value)
    {
        parent::__construct(sprintf(
            "Unrecognized pattern in serialization structure definition:\n%s => %s",
            Utils::representValue($key),
            Utils::representValue($value)
        ));
    }
}