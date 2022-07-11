<?php

namespace App\Serializers;

use App\Entities\User;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\FieldBehaviors\ModifyValueBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use Framework\Serializer\Serializer;

class BasicUserSerializer extends Serializer
{
    public static string $serializesClass = User::class;
    public static ?string $variant = 'basic';

    public function getSerializationStructure(): ?array
    {
        return [
            'username',
            'profilePic' => new ChangeNameBehavior('pic')
        ];
    }

    /**
     * @param object|User $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->username;
    }
}