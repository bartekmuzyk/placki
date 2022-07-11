<?php

namespace App\Serializers;

use App\Entities\PostComment;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use Framework\Serializer\Serializer;

class PostCommentSerializer extends Serializer
{
    public static string $serializesClass = PostComment::class;

    public function getSerializationStructure(): ?array
    {
        return [
            'id',
            'content',
            'author' => new SerializeBehavior('basic'),
            'post' => $this->multi(
                new SerializeBehavior(primitive: true),
                new ChangeNameBehavior('postId')
            )
        ];
    }

    /**
     * @param object|PostComment $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->id;
    }
}