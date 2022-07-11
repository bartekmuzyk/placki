<?php

namespace Framework\Serializer\Exception;

class ConverterNotDefined extends InvalidBehaviorException
{
    public function __construct(string $behaviorName, string $objectClassName, string $propertyName, string $mandatoryConverterInterfaceName)
    {
        parent::__construct(
            $behaviorName, $objectClassName, $propertyName,
            "A converter implementing $mandatoryConverterInterfaceName is mandatory for this type of conversion, but no implementation was found."
        );
    }
}