<?php

namespace App\Serializers;

use App\Entities\Post;
use App\Services\DateFormatterService;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\FieldBehaviors\ConvertDateBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use Framework\Serializer\Serializer;

class PostSerializer extends Serializer
{
    public static string $serializesClass = Post::class;

    public function getSerializationStructure(): ?array
    {
        return [
            'id',
            'content',
            'author' => new SerializeBehavior('basic'),
            'group' => new SerializeBehavior('basic'),
            'likedBy' => new SerializeBehavior('basic', true),
            'commentCount' => fn(Post $post) => $post->comments->count(),
            'attachments' => new SerializeBehavior('post'),
            'at' => new ConvertDateBehavior(DateFormatterService::FORMAT_HUMAN)
        ];
    }

    /**
     * @param object|Post $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->id;
    }
}