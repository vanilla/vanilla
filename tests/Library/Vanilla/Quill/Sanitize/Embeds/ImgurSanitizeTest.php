<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class ImgurSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => null,
                        "type" => "imgur",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => null,
                        "width" => null,
                        "attributes" => ["postID" => $content, "isAlbum" => $content]
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
