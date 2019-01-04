<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class TwitchSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "twitch",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            "videoID" => $content,
                            "embedUrl" => $content,
                        ],
                    ],
                ],
            ],
            ["insert" => "\n\n"],
        ];

        return $operations;
    }
}
