<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class SoundCloudSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => null,
                        "type" => "soundcloud",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => $content,
                        "width" => null,
                        "attributes" => [
                            "visual" => $content,
                            "showArtwork" => $content,
                            "track" => $content,
                            ]
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
