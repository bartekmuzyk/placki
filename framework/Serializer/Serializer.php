<?php

namespace Framework\Serializer;

abstract class Serializer
{
    public static string $serializesClass = '';
    public static ?string $variant = null;

    /**
     * may return <b>null</b> if the object is not chosen to support advanced serializing. make sure that either this
     * method or {@link Serializer::serializePrimitive()} is defined and returns a value other than null.
     * @return array|null
     */
    public abstract function getSerializationStructure(): ?array;

    /**
     * may return <b>null</b> if the object is not chosen to support primitive serializing. make sure that either this
     * method or {@link Serializer::getSerializationStructure()} is defined and returns a value other than null.
     * @return array|null
     */
    public abstract function serializePrimitive(object $object): string|int|bool|null;

    /**
     * @param FieldBehavior ...$behaviors
     * @return MultipleBehaviors
     */
    protected function multi(FieldBehavior ...$behaviors): MultipleBehaviors
    {
        return new MultipleBehaviors($behaviors);
    }
}