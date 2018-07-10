<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class TwitterSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "twitter",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => null,
                        "width" => null,
                        "attributes" => ["statusID" => $content]
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
