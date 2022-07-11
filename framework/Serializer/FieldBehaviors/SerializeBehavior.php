<?php

namespace Framework\Serializer\FieldBehaviors;

use Framework\Serializer\FieldBehavior;
use Framework\Serializer\Serializer;

class SerializeBehavior extends FieldBehavior
{
    public ?string $variant;
    public bool $primitive;

    /**
     * this behavior will serialize the value at the specified field using the appropriate serializer for this type (and
     * variant if specified and if the serializers for this type have multiple variants).
     * @param string|null $variant if <b>null</b>, the only available variant for the type of this field will be chosen.
     * if this value is <b>null</b>, but there are multiple serializer variants for this type, an error will be thrown.
     * if this value is a <b>string</b>, but the serializer for this type has only one unnamed variant, an error will be
     * thrown.
     * @param bool $primitive whether to use {@link Serializer::serializePrimitive()} when serializing the object,
     * instead of {@link Serializer::getSerializationStructure()} to convert the object to a more complex structure.
     */
    public function __construct(?string $variant = null, bool $primitive = false)
    {
        $this->variant = $variant;
        $this->primitive = $primitive;
    }
}