<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;

/**
 * Schema to validate shape of some media upload metadata.
 */
class VanillaMediaSchema extends Schema {

    /**
     * Override constructor to initialize schema.
     *
     * @param bool $withDbFields Whether or not DB related fields should be optional in the schema.
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

        $ownDBFields = [
            'mediaID:i', // The ID of the record.
            'dateInserted:dt', // When the media item was created.
            'insertUserID:i', // The user that created the media item.
            'foreignType:s|n', // Table the media is linked to.
            'foreignID:i|n', // The ID of the table
        ];
        if (!$withDbFields) {
            $fields += $ownDBFields;
        }

        parent::__construct($this->parseInternal($fields));
    }
}
