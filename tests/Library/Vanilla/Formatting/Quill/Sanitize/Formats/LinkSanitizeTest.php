<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\Formats;

use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\SanitizeTest;
use VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize\TestAttributesTrait;

class LinkSanitizeTest extends SanitizeTest {

    use TestAttributesTrait;

    /**
     * @inheritdoc
     */
    protected function attributeOperations(): array {
        $operations = [
            [
                [
                    "insert" => "Hello world.",
                    "attributes" => ["link" => "#VALUE#"]
                ]
            ]
        ];
        return $operations;
    }

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            [
                "insert" => $content,
                "attributes" => ["link" => $content]
            ]
        ];
        return $operations;
    }
}
