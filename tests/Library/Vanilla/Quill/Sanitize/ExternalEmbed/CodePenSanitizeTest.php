<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\ExternalEmbed;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;
use VanillaTests\Library\Vanilla\Quill\Sanitize\CSSInjectionTrait;

class CodePenSanitizeTest extends SanitizeTest {

    use CSSInjectionTrait;

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
                    ]
                ]
            ],
            ["insert" => "\n"]
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
                            'style' => [
                                'width' => $content,
                                'overflow' => $content,
                            ],
                        ],
                    ]
                ]
            ],
            ["insert" => "\n"]
        ];

        return $operations;
    }
}
