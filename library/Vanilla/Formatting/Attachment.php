<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Schema\Schema;

/**
 * Plain old object to represent and attachment.
 */
class Attachment implements \JsonSerializable {

    /** @var string The file name of the attachment. */
    public $name;

    /** @var string The URL pointing to the file. */
    public $url;

    /** @var int */
    public $mediaID;

    /** @var \DateTimeInterface The date the attachment was uploaded. */
    public $dateInserted;

    /** @var int The size of the attachment in bytes. */
    public $size;

    /** @var string The string type of the attachment. */
    public $type;

    /**
     * Lazily load the schema for the attachment.
     *
     * @return Schema
     */
    private static function getSchema(): Schema {
        static $schema;
        if ($schema === null) {
            $schema = Schema::parse([
                'mediaID:i',
                'name:s',
                'mediaID:i',
                'dateInserted:dt',
                'size:i',
                'type:s',
                'url:s',
            ]);
        }
        return $schema;
    }

    /**
     * Create an attachment from an untrusted array.
     *
     * @param array $data The data to validate and convert.
     *
     * @return Attachment
     * @throws \Garden\Schema\ValidationException If the schema validation fails.
     */
    public static function fromArray(array $data): Attachment {
        $validated = self::getSchema()->validate($data);
        $attachment = new Attachment();
        $attachment->mediaID = $validated['mediaID'];
        $attachment->url = $validated['url'];
        $attachment->dateInserted = $validated['dateInserted'];
        $attachment->name = $validated['name'];
        $attachment->size = $validated['size'];
        $attachment->type = $validated['type'];
        return $attachment;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return [
            'name' => $this->name,
            'dateInserted' => $this->dateInserted,
            'mediaID' => $this->mediaID,
            'size' => $this->size,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
