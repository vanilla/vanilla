<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\ImageResizer;

/**
 * Schema to validate shape of some media upload metadata.
 */
class VanillaMediaSchema extends Schema {

    /**
     * Override constructor to initialize schema.
     *
     * @param bool $withDbFields Whether or not DB fields should be included in the schema.
     */
    public function __construct(bool $withDbFields) {
        $fields = [
            'url:s', // The URL of the file.
            'name:s', // The original filename of the upload.
            'type:s', // MIME type
            'size:i', // 'File size in bytes

            // Images only.
            'width:i|n?', // Image width
            'height:i|n?', // Image height
        ];

        if ($withDbFields) {
            $ownDBFields = [
                'mediaID:i', // The ID of the record.
                'dateInserted:dt', // When the media item was created.
                'insertUserID:i', // The user that created the media item.
                'foreignType:s|n', // Table the media is linked to.
                'foreignID:i|n', // The ID of the table
                'foreignUrl:s?'
            ];

            $fields = array_merge($fields, $ownDBFields);
        }

        parent::__construct($this->parseInternal($fields));
    }

    /**
     * Normalize a media DB row into a format that matches this schema.
     *
     * @param array $row
     * @return array
     */
    public static function normalizeFromDbRecord(array $row): array {
        $row['foreignID'] = $row['ForeignID'] ?? null;
        $row['foreignType'] = $row['ForeignTable'] ?? null;

        if (array_key_exists('Path', $row)) {
            $parsed = \Gdn_Upload::parse($row['Path']);
            $row['url'] = $parsed['Url'];

            $ext = pathinfo($row['url'], PATHINFO_EXTENSION);
            if (in_array($ext, array_keys(ImageResizer::getExtType()))) {
                $row['height'] = $row['ImageHeight'] ?? null;
                $row['width'] = $row['ImageWidth'] ?? null;
            }
        } else {
            $row['url'] = null;
        }

        $schemaRecord = ApiUtils::convertOutputKeys($row);
        $schema = new VanillaMediaSchema(true);
        return $schema->validate($schemaRecord);
    }
}
