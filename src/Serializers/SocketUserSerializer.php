<?php

namespace App\Serializers;

use App\Entities\User;
use Framework\Serializer\Serializer;

class SocketUserSerializer extends Serializer
{
    public static string $serializesClass = User::class;
    public static ?string $variant = 'socket';

    public function getSerializationStructure(): ?array
    {
        return [
            'username',
            'authorizedTo' => function(User $user) {
                $authorizedToRooms = [];

                foreach ($user->joinedGroups as $group) $authorizedToRooms[] = "group:$group->id";
                foreach ($user->ownedGroups as $group) $authorizedToRooms[] = "group:$group->id";

                return $authorizedToRooms;
            }
        ];
    }

    public function serializePrimitive(object $object): string|int|bool|null
    {
        return null;
    }
}