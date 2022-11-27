<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Models\RowAttachment;

class AttachmentsListLeaf extends LeafNode
{
    /**
     * @param array<RowAttachment> $attachments
     */
    public static function buildAttachmentsArray(array $attachments): array
    {
        $array = [];

        foreach ($attachments as $attachment) {
            $array[$attachment->fileName] = [
                "id" => intval($attachment->id),
                "uploader" => [
                    "id" => intval($attachment->uploaderId),
                    "firstName" => $attachment->uploaderFirstName,
                    "lastName" => $attachment->uploaderLastName
                ],
                "size" => intval($attachment->size),
                "created" => $attachment->created
            ];
        }

        return $array;
    }

    public function get(array $path): array
    {
        return self::buildAttachmentsArray(RowAttachment::loadAll(RecordsNode::$record->id));
    }
}
