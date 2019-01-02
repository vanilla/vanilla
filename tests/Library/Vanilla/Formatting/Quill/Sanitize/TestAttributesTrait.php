<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize;

trait TestAttributesTrait {

    /**
     * Get an array of testable attribute
     *
     * @return array
     */
    abstract protected function attributeOperations(): array;

    /**
     * Get an array of bad strings for testing sanitization.
     *
     * @return array
     */
    private function badStrings(): array {
        $result = [
            "<script src=http://xss.rocks/xss.js></script>",
            "<script src=\"http://xss.rocks/xss.js\"></script>",
            "'';!--\"<xss>=&{()}"
        ];
        return $result;
    }

    /**
     * Get a list of all combinations of attribute entries and bad strings.
     *
     * @return array
     */
    public function provideBadAttributes(): array {
        $badStrings = $this->badStrings();
        $attributeOperations = $this->attributeOperations();

        $result = [];
        foreach ($attributeOperations as $operations) {
            foreach ($badStrings as $string) {
                $result[] = [$operations, $string];
            }
        }

        return $result;
    }

    /**
     * Test sanitizing the attributes of a blot.
     *
     * @param array $operations
     * @param string $badAttribute
     * @dataProvider provideBadAttributes
     */
    public function testSanitizeBadAttribute(array $operations, string $badAttribute) {
        // Replace array members with a value of #VALUE# to the value of $badAttribute.
        array_walk_recursive($operations, function(&$value) use ($badAttribute) {
            if ($value === "#VALUE#") {
                $value = $badAttribute;
            }
        });
        $this->assertSanitized($operations, $badAttribute);
    }
}
