<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize;

class HeadingSanitizeTest extends SanitizeTest {

    use TestAttributesTrait;

    /**
     * @inheritdoc
     */
    protected function attributeOperations(): array {
        $result = [
            [
                ["insert" => "Hello world."],
                [
                    "attributes" => ["header" => "#VALUE#"],
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
                "attributes" => ["header" => 1],
                "insert" => "\n"
            ]
        ];
        return $operations;
    }
}
