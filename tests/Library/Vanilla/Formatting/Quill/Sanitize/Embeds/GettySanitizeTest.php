<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class GettySanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "getty",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            'id' => $content,
                            'sig' => $content,
                            'items' => $content,
                            'isCaptioned' => $content,
                            'is360' => $content,
                            'tld' => $content,
                            'postID' => $content,
                        ],
                    ],
                ],
            ],
            ["insert" => "\n"],
        ];

        return $operations;
    }
}
