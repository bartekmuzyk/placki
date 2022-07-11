<?php

namespace Framework\Serializer\FieldBehaviors;

use Framework\Serializer\FieldBehavior;

class ConvertDateBehavior extends FieldBehavior
{
    public string $format;

    public function __construct(string $format)
    {
        $this->format = $format;
    }
}