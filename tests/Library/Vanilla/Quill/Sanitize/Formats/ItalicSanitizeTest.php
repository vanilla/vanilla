<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize\Formats;

use VanillaTests\Library\Vanilla\Quill\Sanitize\SanitizeTest;

class ItalicSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => $content,
                "attributes" => [ "italic" => true ]
            ]
        ];
        return $operations;
    }
}
