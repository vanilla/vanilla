<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill;

use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Renderer;

class SanitizeTest extends TestCase {

    /**
     * Provide bad strings for testing sanitization.
     *
     * @return array
     */
    public function provideBadStrings(): array {
        $result = [
            ["<script src=http://xss.rocks/xss.js></script>"],
            ["<script src=\"http://xss.rocks/xss.js\"></script>"],
            ["';alert(String.fromCharCode(88,83,83))//';alert(String.fromCharCode(88,83,83))//\";
alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//--
></script>\">'><script>alert(String.fromCharCode(88,83,83))</script>"],
            ["'';!--\"<xss>=&{()}"]
        ];
        return $result;
    }

    /**
     * Verify contents are properly sanitized during rendering.
     *
     * @param string $badString
     * @dataProvider provideBadStrings
     */
    public function testSanitize(string $badString) {
        /** @var Renderer $renderer */
        $renderer = Gdn::getContainer()->get(Renderer::class);
        $result = $renderer->render([
            ["insert" => $badString."\n"]
        ]);
        $this->assertNotContains($badString, $result);
    }
}
