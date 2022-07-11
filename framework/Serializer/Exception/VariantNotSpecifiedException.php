<?php

namespace Framework\Serializer\Exception;

class VariantNotSpecifiedException extends SerializerException
{
    public function __construct(string $serializedObjectClassName)
    {
        parent::__construct("Specifying a variant to serialize an object of type $serializedObjectClassName is mandatory.");
    }
}