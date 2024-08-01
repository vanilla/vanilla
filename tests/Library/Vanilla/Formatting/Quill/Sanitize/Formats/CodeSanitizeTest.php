<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Formats;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

class CodeSanitizeTest extends SanitizeTest
{
    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array
    {
        $operations = [
            [
                "insert" => $content,
                "attributes" => ["codeBlock" => true],
            ],
        ];
        return $operations;
    }
}
