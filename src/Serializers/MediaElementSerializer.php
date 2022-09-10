<?php

namespace App\Serializers;

use App\Entities\MediaElement;
use App\Services\DateFormatterService;
use Framework\Serializer\FieldBehaviors\ConvertDateBehavior;
use Framework\Serializer\FieldBehaviors\GeneratedValueBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use Framework\Serializer\Serializer;

class MediaElementSerializer extends Serializer
{
    public static string $serializesClass = MediaElement::class;

    public function getSerializationStructure(): ?array
    {
        return [
            'id',
            'mediaType',
            'mimeType',
            'name',
            'sizeText',
            'uploadedBy' => new SerializeBehavior('basic'),
            'description',
            'album',
            'thumbnail',
            'visibility',
            'uploadedAt' => new ConvertDateBehavior(DateFormatterService::FORMAT_HUMAN),
            'viewedBy' => new SerializeBehavior('basic', true),
            'likedBy' => new SerializeBehavior('basic', true),
            'shareUuid' => fn(MediaElement $mediaElement) => $mediaElement->shared?->id
        ];
    }

    /**
     * @param MediaElement $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->id;
    }
}