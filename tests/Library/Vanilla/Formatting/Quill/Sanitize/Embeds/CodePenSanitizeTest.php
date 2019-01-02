<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;
use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\LinkSanitizeTrait;

class CodePenSanitizeTest extends SanitizeTest {

//    use LinkSanitizeTrait;

    /**
     * @inheritdoc
     */
    protected function cssOperations(string $string): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => "http://codepen.io/example",
                        "type" => "codepen",
                        "name" => null,
                        "body" => null,
                        "photoUrl" => null,
                        "height" => 300,
                        "width" => null,
                        "attributes" => [
                            "id" => "example",
                            "embedUrl" => "http://codepen.io/example/embed/preview",
                            'style' => [
                                'width' => $string,
                                'overflow' => $string,
                            ],
                        ],
                    ],
                ],
            ],
            ["insert" => "\n"],
        ];

        return $operations;
    }

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "codepen",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [
                            'id' => $content,
                            'embedUrl' => $content,
                            'style' => [
                                'width' => $content,
                                'overflow' => $content,
                            ],
                        ],
                    ],
                ],
            ],
            ["insert" => $content],
        ];

        return $operations;
    }
}
