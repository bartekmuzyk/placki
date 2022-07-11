<?php

namespace App\Serializers;

use App\Entities\Group;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\Serializer;

class BasicGroupSerializer extends Serializer
{
    public static string $serializesClass = Group::class;
    public static ?string $variant = 'basic';

    public function getSerializationStructure(): ?array
    {
        return [
            'id',
            'name',
            'description',
            'picFilename' => new ChangeNameBehavior('pic')
        ];
    }

    /**
     * @param object|Group $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->id;
    }
}