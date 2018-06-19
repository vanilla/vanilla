<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\ExternalEmbed;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class GiphySanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => null,
                        "type" => "giphy",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => ["postID" => $content, "url" => $content]
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
