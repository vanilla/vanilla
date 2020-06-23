<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\PregReplaceCallbackProcessor;
use Vanilla\Formatting\Html\Processor\StripEmbedsProcessor;
use Vanilla\Formatting\Html\Processor\StripImagesProcessor;
use Vanilla\Formatting\Html\Processor\TrimWordsProcessor;

/**
 * Class DomUtilsProcessorsTest for testing DomUtils processors
 * @package VanillaTests\Library\Vanilla\Utility
 */
class DomUtilsProcessorsTest extends DomUtilsTest {


    /**
     * Test striping images with variable expected
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideStripImagesTests
     */
    public function testStripImages(string $input, string $expected): void {
        $dom = new HtmlDocument($input);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($input, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom->applyProcessors([StripImagesProcessor::class]);

        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test striping embeds
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideStripEmbedsTests
     */
    public function testStripEmbeds(string $input, string $expected): void {
        $dom = new HtmlDocument($input);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($input, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom->applyProcessors([StripEmbedsProcessor::class]);
        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test truncating words.
     *
     * @param ?int $wordCount
     * @param string $html
     * @param string $expected
     * @dataProvider provideTrimWordsTests
     */
    public function testTrimWords($wordCount, $html, $expected): void {
        $domDocument = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $trimWordsProcessor = empty($wordCount) ? new TrimWordsProcessor($domDocument) : new TrimWordsProcessor($domDocument, $wordCount);
        $domDocument->applyProcessors([$trimWordsProcessor]);
        $actual = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test preg replace with callback.
     *
     * @param int $expectedCount
     * @param string $patternText
     * @param string $input
     * @param int $expected
     * @dataProvider providePregReplaceCallbackTests
     */
    public function testPregReplaceCallback($expectedCount, string $patternText, string $input, string $expected): void {
        $domDocument = new HtmlDocument($input);
        $this->assertHtmlStringEqualsHtmlString($input, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");
        $pregReplaceCallbackProcessor = new PregReplaceCallbackProcessor($domDocument, ['`(?<![\pL\pN])'.$patternText.'(?![\pL\pN])`isu'], function (array $matches): string {
            return '***';
        });
        $domDocument->applyProcessors([$pregReplaceCallbackProcessor]);
        $actual = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }
}
