<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class VimeoSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "vimeo",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            "thumbnail_width" => $content,
                            "thumbnail_height" => $content,
                            "videoID" => $content,
                            "embedUrl" => $content,
                        ],
                    ],
                ],
            ],
            ["insert" => "\n$content\n"],
        ];

        return $operations;
    }
}
