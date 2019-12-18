<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for htmlEntityDecode().
 */

class HtmlEntityDecodeTest extends TestCase {

    /**
     * Test {@link htmlEntityDecode() against several scenarios.
     *
     * @param string $testString The string to decode.
     * @param int $testQuote_style One of the `ENT_*` constants.
     * @param string $testCharset The character set of the string.
     * @param string $expected The expected result.
     * @dataProvider provideTestHtmlEntityDecodeArrays
     */
    public function testHtmlEntityDecode($testString, $testQuote_style, $testCharset, $expected) {
        $actual = htmlEntityDecode($testString, $testQuote_style, $testCharset);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testHtmlEntityDecode()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestHtmlEntityDecodeArrays() {
        $r = [
            'testWithDefaultSettings' => [
                "I'll &quot;walk&quot; the &lt;b&gt;dog&lt;/b&gt; now",
                ENT_QUOTES,
                'utf-8',
                "I'll \"walk\" the <b>dog</b> now",
            ],
            'testWithDefaultSettings2' => [
                "A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;",
                ENT_QUOTES,
                'utf-8',
                "A 'quote' is <b>bold</b>",
            ],
            'testWithEntNoQuotes' => [
                "A &#039;quote&#039; is &lt;b&gt;bold&lt;/b&gt;",
                ENT_NOQUOTES,
                'utf-8',
                "A 'quote' is <b>bold</b>",
            ],
            'testWithHexCode' => [
                '&#x20AC;',
                ENT_QUOTES,
                'utf-8',
                '€',
            ],
        ];

        return $r;
    }
}
