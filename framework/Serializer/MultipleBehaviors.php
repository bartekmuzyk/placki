<?php

namespace Framework\Serializer;

final class MultipleBehaviors
{
    /**
     * @var FieldBehavior[]
     */
    public array $behaviors;

    /**
     * @param FieldBehavior[] $behaviors
     */
    public function __construct(array $behaviors)
    {
        $this->behaviors = $behaviors;
    }
}