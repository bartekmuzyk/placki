<?php

namespace Framework\Serializer\Exception;

class InvalidTargetClassException extends SerializerException
{
    public function __construct(string $serializerClassName)
    {
        parent::__construct(
            "The serializer $serializerClassName has an invalid value for the \$serializesClass property."
        );
    }
}