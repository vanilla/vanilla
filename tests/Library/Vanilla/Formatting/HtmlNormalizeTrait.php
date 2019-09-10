<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use voku\helper\HtmlMin;

/**
 * Trait for normalizing some HTML to make test assertions easier.
 */
trait HtmlNormalizeTrait {
    /** @var HtmlMin */
    private $minifier;

    /**
     * Minify some HTML to help normalize it's shape.
     *
     * @param string $html
     *
     * @return string
     */
    private function minifyHTML(string $html): string {
        if ($this->minifier === null) {
            $this->minifier = new HtmlMin();
            $this->minifier->doRemoveSpacesBetweenTags()
                ->doRemoveWhitespaceAroundTags()
                ->doSortHtmlAttributes()
                ->doRemoveOmittedHtmlTags(false)
            ;
        }
        return $this->minifier->minify($html);
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
    protected function normalizeHtml($html) {
        $html = $this->stripZeroWidthWhitespace($html);
        $html = $this->minifyHTML($html);
        // Stub out SVGs
        $html = preg_replace("/(<svg.*?<\/svg>)/", "<SVG />", $html);
        $html = preg_replace("/\>\</", ">\n<", $html);
        $html = preg_replace("/ \</", "<", $html);
        return $html;
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
     *
     * @param string $expected
     * @param string $actual
     * @param string|null $message
     */
    protected function assertHtmlStringEqualsHtmlString(string $expected, string $actual, string $message = null) {
        $expected = $this->normalizeHtml($expected);
        $actual = $this->normalizeHtml($actual);
        $this->assertEquals($expected, $actual, $message);
    }
}
