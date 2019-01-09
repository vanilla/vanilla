<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize;

class BulletedListSanitizeTest extends SanitizeTest {

    use TestAttributesTrait;

    /**
     * @inheritdoc
     */
    protected function attributeOperations(): array {
        $result = [
            [
                ["insert" => "Hello world."],
                [
                    "attributes" => [
                        "list" => "bullet",
                        "indent" => "#VALUE#"
                    ],
                    "insert" => "\n"
                ]
            ]
        ];
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function insertContentOperations(string $content): array {
        $operations = [
            ["insert" => $content],
            [
                "attributes" => ["list" => "bullet"],
                "insert" => "$content"
            ]
        ];
        return $operations;
    }
}
