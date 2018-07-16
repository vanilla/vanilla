<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Formats;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class BoldSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => $content,
                "attributes" => [ "bold" => true ]
            ]
        ];
        return $operations;
    }
}
