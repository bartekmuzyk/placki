<?php

namespace App\Serializers;

use App\Entities\Attachment;
use App\Services\CDNService;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\FieldBehaviors\GeneratedValueBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use Framework\Serializer\Serializer;
use Framework\Utils\Utils;

class PostAttachmentSerializer extends Serializer
{
    private const ATTACHMENT_PATH_PREFIX = CDNService::CDN_DIR . '/attachments/';

    public static string $serializesClass = Attachment::class;
    public static ?string $variant = 'post';

    public function getSerializationStructure(): ?array
    {
        return [
            'id',
            'originalFilename' => new ChangeNameBehavior('filename'),
            'extension',
            'post' => $this->multi(
                new SerializeBehavior(primitive: true),
                new ChangeNameBehavior('postId')
            ),
            'dimensions' => function(Attachment $attachment) {
                if (Utils::isExtensionImage($attachment->extension)) {
                    [$width, $height] = getimagesize(
                        self::ATTACHMENT_PATH_PREFIX . $attachment->id
                    );
                } else {
                    $width = $height = 0;
                }

                return ['w' => $width, 'h' => $height];
            },
            'size' => function(Attachment $attachment) {
                $size = filesize(self::ATTACHMENT_PATH_PREFIX . $attachment->id);

                return Utils::getHumanReadableSize($size);
            },
            'mimetype' => fn(Attachment $attachment) => mime_content_type(self::ATTACHMENT_PATH_PREFIX . $attachment->id)
        ];
    }

    /**
     * @param object|Attachment $object
     * @return string|int|bool|null
     */
    public function serializePrimitive(object $object): string|int|bool|null
    {
        return $object->id;
    }
}