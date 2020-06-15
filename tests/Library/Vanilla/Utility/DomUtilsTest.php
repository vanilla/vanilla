<?php

/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use DOMDocument;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Utility\StringUtils;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

class DomUtilsTest extends MinimalContainerTestCase {
    use HtmlNormalizeTrait;
    use AssertsFixtureRenderingTrait;

//    public function testStripEmbeds() {
//        $html = <<<HTML
//        <div class="embedExternal embedImage">
//    <div class="embedExternal-content">
//        <a class="embedImage-link" href="https://testsite.com/uploads/76B8X91XXJC8/PN5HO0RDQMJX.jpeg" rel="nofollow noreferrer noopener ugc" target="_blank">
//            <img
//                class="embedImage-img"
//                src="https://testsite.com/uploads/76B8X91XXJC8/PN5HO0RDQMJX.jpeg"
//                alt="0.jpeg"
//            />
//        </a>
//    </div>
//</div>
//<p><br></p>
//HTML;
//        $expected = '<p><br></p>';
//        $dom = new HtmlDocument($html);
//        $embeds = ['js-embed', 'embedResponsive', 'embedExternal', 'embedImage', 'VideoWrap', 'iframe'];
//        DomUtils::stripEmbeds($dom->getDom(),  $embeds);
//        $actual = $dom->getInnerHtml();
//        $this->assertSame($expected, trim($actual));
//        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
//    }

    /**
     * Test various format tag formats.
     *
     * @param string $html
     * @param string $expected
     * @dataProvider provideImageData
     */
//    public function testStripImages(string $html, string $expected): void {
//        $dom = new DOMDocument();
//        $dom->loadHTML($html);
//        DomUtils::stripImages($dom);
//        $result = $dom->saveHTML();
//        $this->assertHtmlStringEqualsHtmlString($expected, $result);
//    }

    /**
     * Test truncating words.
     *
     * @param int $wordCount
     * @param string $html
     * @param string $expected
     * @dataProvider provideTrimWordsTests
     */
//    public function testTrimWords($wordCount, $html, $expected) {
//        $domDocument = new HtmlDocument($html);
//
//        // This assertion tests against bugs in the HtmlDocument class itself.
//        $this->assertHtmlStringEqualsHtmlString($html, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");
//
//        $dom = $domDocument->getDom();
//        DomUtils::trimWords($dom, $wordCount);
//        $actual = $domDocument->getInnerHtml();
//        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
//    }

    /**
     * Test the DomUtils methods with an array of all fixture inputs.
     *
     * @dataProvider groupProviders
     */
    public function testGroupInputs(array $providers) {
        foreach ($providers as $method => $fixtureDir) {
            $this->getFixturePassesForUtils($fixtureDir, $method);
            list($expected, $actual) = $this->getFixturePassesForUtils($fixtureDir, $method);
            $this->assertHtmlStringEqualsHtmlString($expected, $actual);
        }
    }

    /**
     * @param string $html
     * @param string $expected
     * @dataProvider provideStripImagesTests
     */
    public function testStripImages(string $html, string $expected): void {
        $dom = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        DomUtils::stripImages($dom->getDom());
        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * @param string $html
     * @param string $expected
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

    public function provideStripImagesFixedTests(): array {
        $r = [
            'no strip' => ['a b c'],
            'strip before' => ['<img src="a.png" /> a b c'],
            'strip after' => [' a b <img src="http://example.com/a.png" /> c'],
            'closing tag' => [' a b <img src="http://example.com/a.png" ></img> c'],
        ];

        return $r;
    }

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
     * @return array
     */
    public function groupProviders(): array {
        $providers = [
            'DomUtils::stripImages' => [$this->provideImageFixture()],
        ];

        return $providers;
    }

    /**
     * @return array
     */
    public function provideImageFixture(): array {
        return $this->createFixtureDataProvider('domutils');
    }

    /**
     * Assert html input and expected for DomUtils tests
     *
     * @param string $fixtureDir
     * @param callable $callable
     * @param mixed $param
     */
    public function getFixturePassesForUtils(string $fixtureDir, callable $callable, $param = false) {
        list($input, $expectedHtml, $expectedText) = $this->getFixture($fixtureDir);
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($input);
        if ($param) {
            call_user_func($callable, [$dom, $param]);
        } else {
            call_user_func($callable, [$dom]);
        }
        $internalHtml = $dom->saveHTML();
        $htmlDocument = new HtmlDocument($internalHtml);
        $result = $htmlDocument->getInnerHtml();
        return [$expectedHtml, $result];
    }

    /**
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
     * Provide tests for `testStripImages()`.
     * @return array
     */
//    public function provideImageData(): array {
//        $r = [
//            'BBCode' => ['', '<img src="http://oracle.spawn/uploads/editor/7b/ojeblp0ylk7w.jpeg" alt="ojeblp0ylk7w.jpeg" class="embedImage-img importedEmbed-img" />'],
//            'Html' => ['', '<img src="http://test.spawn/uploads/editor/te/k6rq7bu2jzub.jpeg" alt="" />'],
//            'Rich' => ['', '<img src="http://test.spawn/uploads/editor/ng/yjf6b4r40ydk.jpeg" alt="" class="embedImage-img importedEmbed-img"></img>'],
//        ];
//
//        return $r;
//    }

    /**
     * Provide tests for `TestTruncateWords()`.
     * @return array
     */
//    public function provideTrimWordsTests(): array {
//        $r = [
//            'Test10Words' => [
//                10,
//                '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi welsh onion daikon amaranth tatsoi '.
//                    'tomatillo melon azuki bean garlic.</p><br><p>Gumbo beet greens corn soko endive gumbo gourd. '.
//                    'Parsley shallot courgette tatsoi pea sprouts fava bean collard greens dandelion okra wakame tomato.</p>',
//                '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi</p>'
//            ],
//            'Test2Words' => [2, 'One dollar', 'One dollar'],
//            'Test5Words' => [4, 'One dollar and eighty-seven cents', 'One dollar and eighty-seven'],
//            'mixed nested' => [2, 'a <b>b</b> c', 'a <b>b</b>'],
//            "short html" => [4, 'a b', 'a b'],
//            'heavily nested' => [
//                2,
//                '<div><div><div><div>this</div> is a word</div></div> <b>okay?</b></div>',
//                '<div><div><div><div>this</div> is</div></div></div>'
//            ],
//        ];
//
//        return $r;
//    }
}
