<?php

namespace Framework\Serializer\Exception;

/**
 * thrown when there are multiple serializers where at least one has a defined variant and at least one does not define
 * a variant.
 */
class MixedUpVariantException extends SerializerException
{
    public function __construct(string $serializerClassName)
    {
        parent::__construct(
            "The serializer $serializerClassName has at least two versions where one defines a variant and one 
            does not. This is not an acceptable combination."
        );
    }
}