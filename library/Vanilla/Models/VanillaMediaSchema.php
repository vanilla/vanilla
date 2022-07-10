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
            'displaySize:s?' => [ // Image display size "large" | "medium" | "small"
                'default' => 'large',
                'enum' => ['large', 'medium', 'small']
            ],
            'float:s?' => [ // Image float "left" | "right"
                'default' => 'none',
                'enum' => ['none', 'left', 'right']
            ],
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
     * Returns the default display size of an image according to it's width.
     * Small images will be less than 200px wide;
     * Medium images will be less than 400px wide;
     * Images are large by default.
     *
     * @param int $imageWidth
     * @return string
     */
    public static function getDefaultDisplaySize(int $imageWidth): string {
        if ($imageWidth <= 200) {
            return "small";
        } elseif ($imageWidth <= 400) {
            return "medium";
        } else {
            return "large";
        }
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

        // Determine the default displaySize of the image from it's size.
        if (!array_key_exists('displaySize', $row) && array_key_exists('width', $row)) {
            $row['displaySize'] = self::getDefaultDisplaySize($row['width']);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($row);
        $schema = new VanillaMediaSchema(true);
        return $schema->validate($schemaRecord);
    }
}
