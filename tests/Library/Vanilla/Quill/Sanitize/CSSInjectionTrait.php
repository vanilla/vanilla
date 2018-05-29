<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill\Sanitize;

trait CSSInjectionTrait {

    /**
     * Get an array of operations to test a CSS values.
     *
     * @param string $string A CSS value.
     * @return array
     */
    abstract protected function cssOperations(string $string): array;

    /**
     * Get CSS injection strings.
     *
     * @return array
     */
    public function provideBadCSS(): array {
        $result = [
            ["https://example.com/pwned.jpg);position:relative;top:0;left:0;width:1000px;height:1000px;"]
        ];
        return $result;
    }

    /**
     * Test sanitizing dynamic CSS in a blot.
     *
     * @param array $operations
     * @param string $string
     * @dataProvider provideBadCSS
     */
    public function testSanitizeCSS(string $string) {
        $operations = $this->cssOperations($string);

        // Test bare value, as well as encoded values.
        $this->assertSanitized($operations, $string);
        $this->assertSanitized($operations, htmlspecialchars($string));
        $this->assertSanitized($operations, htmlentities($string));
    }
}
