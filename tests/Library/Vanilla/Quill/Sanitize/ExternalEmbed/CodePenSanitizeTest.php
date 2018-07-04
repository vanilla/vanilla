<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\ExternalEmbed;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class CodePenSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => null,
                        "type" => "codepen",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => $content,
                        "width" => null,
                        "attributes" => [
                            'id' => $content,
                            'embedUrl' => $content,
                            'style' => $content,
                        ],
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
