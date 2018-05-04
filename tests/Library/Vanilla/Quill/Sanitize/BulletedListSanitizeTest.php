<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize;

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
                "insert" => "\n"
            ]
        ];
        return $operations;
    }
}
