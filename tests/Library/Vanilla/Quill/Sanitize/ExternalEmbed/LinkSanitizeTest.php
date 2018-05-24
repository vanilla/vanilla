<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\ExternalEmbed;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;
use VanillaTests\Library\Vanilla\Quill\Sanitize\CSSInjectionTrait;

class LinkSanitizeTest extends SanitizeTest {

    use CSSInjectionTrait;

    /**
     * @inheritdoc
     */
    protected function cssOperations(string $string): array {
        $result = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => "http://example.com",
                        "type" => "link",
                        "name" => "Example.com",
                        "body" => "Hello world.",
                        "photoUrl" => $string,
                        "height" => null,
                        "width" => null,
                        "attributes" => []
                    ],
                ]
            ],
            ["insert" => "\n"]
        ];
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => ["url" => $content],
                    "type" => "link",
                    "name" => $content,
                    "body" => $content,
                    "photoUrl" => $content,
                    "height" => $content,
                    "width" => $content,
                    "attributes" => []
                ]
            ],
            ["insert" => "\n"]
        ];
        return $operations;
    }
}
