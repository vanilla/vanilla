<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use Vanilla\Formatting\Html\HtmlDocument;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `HtmlDocument` class.
 */
class HtmlDocumentTest extends VanillaTestCase {
    use HtmlNormalizeTrait;

    public const HTML_FULL = <<<HTML
<html lang="en">
<head>
    <meta charset="utf-8">
</head>
<body>
٩(-̮̮̃-̃)۶ ٩(●̮̮̃•̃)۶ ٩(͡๏̯͡๏)۶ ٩(-̮̮̃•̃).
</body>
</html>
HTML;

    public const HTML_TEXT = <<<HTML
٩(-̮̮̃-̃)۶ ٩(●̮̮̃•̃)۶ ٩(͡๏̯͡๏)۶ ٩(-̮̮̃•̃).
HTML;

    public const HTML_SNIPPET = <<<HTML
<div>٩(-̮̮̃-̃)۶ <b>٩(●̮̮̃•̃)۶</b> ٩(͡๏̯͡๏)۶ ٩(-̮̮̃•̃).</div>
HTML;



    /**
     * Test the fragment without wrapping.
     */
    public function testNoWrap(): void {
        $doc = new HtmlDocument(self::HTML_FULL, false);
        $actual = $doc->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString(self::HTML_FULL, $actual);
    }

    /**
     * Make sure an HTML snippet loads/saves properly.
     *
     * @param string $html
     * @dataProvider provideHtmlSnippets
     */
    public function testWrap(string $html): void {
        $doc = new HtmlDocument($html);
        $actual = $doc->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($html, $actual);
    }

    /**
     * Data provider.
     *
     * @return \string[][]
     */
    public function provideHtmlSnippets(): array {
        return [
            'text' => [self::HTML_TEXT],
            'snippet' => [self::HTML_SNIPPET],
        ];
    }
}
