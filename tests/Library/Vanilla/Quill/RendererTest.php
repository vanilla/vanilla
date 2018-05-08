<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Parser;
use Vanilla\Quill\Renderer;
use Vanilla\Quill\Blots;


class RendererTest extends TestCase {

    /**
     * Replace all zero-width whitespace in a string.
     *
     * U+200B zero width space
     * U+200C zero width non-joiner Unicode code point
     * U+200D zero width joiner Unicode code point
     * U+FEFF zero width no-break space Unicode code point
     *
     * @param string $text The string to filter.
     *
     * @return string
     */
    private function stripZeroWidthWhitespace(string $text): string {
        return preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text );
    }

    /**
     * @dataProvider directoryProvider
     */
    public function testRender($dirname) {
        $fixturePath = realpath(__DIR__."/../../../fixtures/editor-rendering/".$dirname);

        $input = file_get_contents($fixturePath . "/input.json");
        $expectedOutput = trim(file_get_contents($fixturePath . "/output.html"));
        $expectedOutput = $this->stripZeroWidthWhitespace($expectedOutput);

        $json = \json_decode($input, true);

        $parser = new Parser();
        $renderer = new Renderer($parser);

        $output = $renderer->render($json);
        $this->assertEquals($expectedOutput, $output);
    }

    public function directoryProvider() {
        return [
            ["inline-formatting"],
            ["paragraphs"],
            ["headings"],
            ["lists"],
            ["emoji"],
            ["blockquote"],
            ["spoiler"],
            ["code-block"],
            ["all"],
            ["all-blocks"],
        ];
    }
}
