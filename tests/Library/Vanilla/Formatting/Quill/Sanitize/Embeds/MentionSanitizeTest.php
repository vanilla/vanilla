<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Embeds;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;

/**
 * Tests to ensure that @mentions are properly sanitized.
 */
class MentionSanitizeTest extends SanitizeTest {

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            ["insert" => ["mention" => [
                "name" => $content,
                "userID" => $content,
            ]]],
            ["insert" => $content],
        ];

        return $operations;
    }
}
