<?php

namespace Framework\Serializer\FieldBehaviors;

use Framework\Serializer\FieldBehavior;

class ChangeNameBehavior extends FieldBehavior
{
    public string $newName;

    /**
     * this behavior will change the name of the key in the serialized version of the object which is currently being
     * serialized. <b>be careful with this behavior</b>, as creating duplicate keys either on purpose or by accident
     * using this behavior, will cause errors in runtime.
     * @param string $newName
     */
    public function __construct(string $newName)
    {
        $this->newName = $newName;
    }
}