<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\ExternalEmbed;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class GettySanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
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
                            ]
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
