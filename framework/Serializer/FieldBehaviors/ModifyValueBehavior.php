<?php

namespace Framework\Serializer\FieldBehaviors;

use Framework\Serializer\FieldBehavior;

class ModifyValueBehavior extends FieldBehavior
{
    /**
     * @var callable
     */
    public $modifier;

    /**
     * @param callable $modifier this callable gets passed the original value as the first and only argument, and should
     * return a new value, or <b>null</b> to leave the value as is.
     */
    public function __construct(callable $modifier)
    {
        $this->modifier = $modifier;
    }
}