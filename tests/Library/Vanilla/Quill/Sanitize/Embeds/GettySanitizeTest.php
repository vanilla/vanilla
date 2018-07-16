<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
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
                        $content => "bad stuff",
                        "url" => null,
                        "type" => "getty",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            'id' => $content,
                            'sig' => $content,
                            'items' => $content,
                            'isCaptioned' => $content,
                            'is360' => $content,
                            'tld'=> $content,
                            'postID' => $content,
                        ],
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
