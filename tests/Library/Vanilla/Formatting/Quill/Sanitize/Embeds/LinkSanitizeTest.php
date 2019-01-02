<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;
use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\LinkSanitizeTrait;

class LinkSanitizeTest extends SanitizeTest {
    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => [
                    "embed-external" => [
                        "url" => $content,
                        "type" => "link",
                        "name" => $content,
                        "body" => $content,
                        "photoUrl" => $content,
                        "height" => $content,
                        "width" => $content,
                        "attributes" => [],
                    ],
                ],
            ],
            ["insert" => "\n"],
        ];

        return $operations;
    }
}
