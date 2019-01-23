<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize;

class SpoilerSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            ["insert" => $content],
            [
                "attributes" => ["spoiler-line" => true],
                "insert" => "$content"
            ]
        ];
        return $operations;
    }
}
