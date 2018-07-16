<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class WistiaSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "wistia",
                        "name" => $content,
                        "body" => null,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            "thumbnail_width" => $content,
                            "thumbnail_height" => $content,
                            "videoID" => $content,
                            "embedUrl" => $content
                        ]
                    ]
                ]
            ],
            ["insert" => "\n\n"]
        ];

        return $operations;
    }
}
