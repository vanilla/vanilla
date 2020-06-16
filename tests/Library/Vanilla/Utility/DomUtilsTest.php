<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Utility\StringUtils;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Class for testing dom utility functions.
 */
class DomUtilsTest extends TestCase {
    use AssertsFixtureRenderingTrait;

    /**
     * Test truncating words.
     *
     * @param int $wordCount
     * @param string $html
     * @param string $expected
     * @dataProvider provideTrimWordsTests
     */
    public function testTrimWords($wordCount, $html, $expected) {
        $domDocument = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom = $domDocument->getDom();
        DomUtils::trimWords($dom, $wordCount);
        $actual = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

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

        DomUtils::stripImages($dom->getDom());
        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test striping images with fix expected
     *
     * @param string $html
     * @dataProvider provideStripImagesFixedTests
     */
    public function testStripImagesFixed(string $html): void {
        $expected = 'a b c';
        $dom = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        DomUtils::stripImages($dom->getDom());
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

        DomUtils::stripEmbeds($dom->getDom());
        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Provide tests for `testStripImagesFixed()`.
     *
     * @return array
     */
    public function provideStripImagesFixedTests(): array {
        $r = [
            'no strip' => ['a b c'],
            'strip before' => ['<img src="a.png" /> a b c'],
            'strip after' => [' a b <img src="http://example.com/a.png" /> c'],
            'closing tag' => [' a b <img src="http://example.com/a.png" ></img> c'],
        ];

        return $r;
    }

    /**
     * Provide tests for `testStripImages()`.
     *
     * @return array
     */
    public function provideStripImagesTests(): array {
        $r = $this->createProviderFromDirectory('domutils/strip-images');

        $r += [
            'no strip' => ['a', 'a'],
            'strip before' => ['<img src="http://example.com/a.png" /> a', 'a'],
            'strip after' => ['a <img src="http://example.com/a.png" />', 'a'],
            'multiple image' => ['a <img src="/a.jpg" /> b <img src="/b.jpg" />', 'a b'],
            'closing tag' => ['a <img src="/a.jpg" ></img>', 'a'],
            'nested' => ['<div><div><p><img src="http://example.com/a.png" /></p> a</div></div>', '<div><div><p></p> a</div></div>'],
        ];

        return $r;
    }

    /**
     * Returns tests providers from directory
     *
     * @param string $subdir
     * @return array
     */
    protected function createProviderFromDirectory(string $subdir): array {
        $provider = $this->createFixtureDataProvider($subdir);
        $r = [];
        foreach ($provider as $row) {
            $dirname = $row[0];
            $shortName = StringUtils::substringLeftTrim($dirname, PATH_ROOT . '/tests/fixtures/');

            $input = file_get_contents($dirname . '/input.html');
            $output = file_get_contents($dirname . '/output.html');

            $r[$shortName] = [$input, $output];
        }
        return $r;
    }

    /**
     * Provide tests for `TestTruncateWords()`.
     *
     * @return array
     */
    public function provideTrimWordsTests(): array {
        $r = [
            'Test10Words' => [
                10,
                '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi welsh onion daikon amaranth tatsoi '.
                    'tomatillo melon azuki bean garlic.</p><br><p>Gumbo beet greens corn soko endive gumbo gourd. '.
                    'Parsley shallot courgette tatsoi pea sprouts fava bean collard greens dandelion okra wakame tomato.</p>',
                '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi</p>'
            ],
            'Test2Words' => [2, 'One dollar', 'One dollar'],
            'Test5Words' => [4, 'One dollar and eighty-seven cents', 'One dollar and eighty-seven'],
            'mixed nested' => [2, 'a <b>b</b> c', 'a <b>b</b>'],
            "short html" => [4, 'a b', 'a b'],
            'heavily nested' => [
                2,
                '<div><div><div><div>this</div> is a word</div></div> <b>okay?</b></div>',
                '<div><div><div><div>this </div> is</div></div></div>'
            ],
        ];

        return $r;
    }

    /**
     * Provide tests for `TestStripEmbeds()`.
     *
     * @return array
     */
    public function provideStripEmbedsTests(): array {
        $r = $this->createProviderFromDirectory('domutils/strip-embeds');
        return $r;
    }
}
