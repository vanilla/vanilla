<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill\Sanitize;

trait LinkSanitizeTrait {

    /**
     * Get an array of operations to test a CSS values.
     *
     * @param string $href A Link value.
     * @return array
     */
    abstract protected function linkOperations(string $href): array;

    /**
     * Get CSS injection strings.
     *
     * @return array
     */
    public function provideBadHrefs(): array {
        $result = [
            ["https://example.com/pwned.jpg);position:relative;top:0;left:0;width:1000px;height:1000px;"],
            ["position:relative;top:0;left:0;width:1000px;height:1000px;"],
        ];
        return $result;
    }

    /**
     * Assert that a the contents of certain operations will be properly sanitized.
     *
     * @param array $operations The operations to render.
     * @param string $badValue The value that should not appear in the rendered output.
     *
     * @throws Exception If the parser or render could not be instantiated.
     */
    protected function assertLinksSanitized(array $operations, string $badHref) {
        $result = $this->render($operations);

        // The contents should've had a unsafe: prepended onto them.
        $this->assertNotContains($badHref, $result);
        $this->assertNotContains(htmlspecialchars($badHref), $result);
        $this->assertNotContains(htmlentities($badHref), $result);
    }


    /**
     * Test sanitizing dynamic CSS in a blot.
     *
     * @param string $href
     * @dataProvider provideBadHrefs
     */
    public function testSanitizeLinks(string $href) {
        $operations = $this->linkOperations($href);
        $this->assertLinksSanitized($operations, $href);
    }
}
