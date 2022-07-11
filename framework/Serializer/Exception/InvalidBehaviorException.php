<?php

namespace Framework\Serializer\Exception;

class InvalidBehaviorException extends SerializerException
{
    public function __construct(
        string $behaviorName, string $objectClassName, string $propertyName, string $additionalInfo = ''
    )
    {
        parent::__construct(
            "The behavior $behaviorName is not suitable for serializing $objectClassName::$propertyName.\n$additionalInfo"
        );
    }
}