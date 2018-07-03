<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Quill\Parser;
use Vanilla\Quill\Renderer;
use voku\helper\HtmlMin;

class RendererTest extends SharedBootstrapTestCase {

    /** @var HtmlMin */
    private $minifier;

    public function __construct(string $name = null, array $data = [], string $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->minifier = new HtmlMin();
        $this->minifier->doRemoveSpacesBetweenTags()
            ->doRemoveWhitespaceAroundTags()
            ->doSortHtmlAttributes()
            ->doRemoveOmittedHtmlTags(false)
        ;

//        $this->minifier->doOptimizeViaHtmlDomParser(false);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRender($dirname) {
        $fixturePath = realpath(__DIR__."/../../../fixtures/editor-rendering/".$dirname);

        $input = file_get_contents($fixturePath."/input.json");
        $expectedOutput = trim(file_get_contents($fixturePath."/output.html"));

        $json = \json_decode($input, true);

        $parser = new Parser();
        $renderer = new Renderer($parser);

        $output = $renderer->render($json);
        $this->assertHtmlStringEqualsHtmlString($expectedOutput, $output);
    }

    public function dataProvider() {

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
        return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
    }

    /**
     * Assert that two strings of HTML are roughly similar. This doesn't work for code blocks.
     */
    private function assertHtmlStringEqualsHtmlString($expected, $actual) {
        $expected = $this->normalizeHtml($expected);
        $actual = $this->normalizeHtml($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Remove whitespace characters from an HTML String. This is good for rough matches.
     *
     * It is not capable of accurately testing code blocks or anything with white-space:pre.
     *
     * @param string $html The html to filter
     *
     * @return string
     */
    private function normalizeHtml($html) {
        $html = $this->stripZeroWidthWhitespace($html);
        $html = $this->minifier->minify($html);
        // Stub out SVGs
        $html = preg_replace("/(<svg.*?<\/svg>)/", "<SVG />", $html);
        $html = preg_replace("/\>\</", ">\n<", $html);
        $html = preg_replace("/ \</", "<", $html);
        return $html;
    }
}
