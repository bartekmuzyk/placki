<?php

namespace Framework\Serializer\FieldBehaviors;

use Framework\Serializer\FieldBehavior;

class GeneratedValueBehavior extends FieldBehavior
{
    /**
     * @var callable
     */
    public $generator;

    public function __construct(callable $generator)
    {
        $this->generator = $generator;
    }
}