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
use PHPUnit\Framework\TestCase;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\Library\Vanilla\Formatting\GdnFormatTest;

class DomUtilsTest extends MinimalContainerTestCase {
    use HtmlNormalizeTrait;

    public function testStripEmbeds()
    {
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
    }

    /**
     * Test various format tag formats.
     *
     * @param string $actual
     * @param string $expected
     * @dataProvider provideImageData
     */
    public function testStripImages($expected, $actual) {
        $dom = new DOMDocument();
        $dom->loadHTML($actual);
        DomUtils::stripImages($dom);
        $result = $dom->saveHTML();
        $this->assertHtmlStringEqualsHtmlString($expected, $result);
    }

    /**
     * Test truncating words.
     *
     * @param string $actual
     * @param string $expected
     * @dataProvider provideTruncateWordsData
     */
    public function testTruncateWordCount($wordCount, $expected, $actual) {
        $domDocument = new HtmlDocument($actual);
        $dom = $domDocument->getDom();
        DomUtils::truncateWords($dom, $wordCount);
        $result = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $result);
    }

    /**
     * Test the DomUtils methods with an array of all fixture inputs.
     *
     * @dataProvider groupProviders
     */
    public function testGroupInputs(array $providers) {
        foreach ($providers as $method => $fixtureDir) {
            $this->assetFixturePassesForUtils($fixtureDir, $method);
        }
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
        return $this->createFixtureDataProvider('/domutils/images');
    }

    /**
     * Assert html input and expected for DomUtils tests
     *
     * @param string $fixtureDir
     * @param callable $callable
     * @param mixed $param
     */
    public function assetFixturePassesForUtils(string $fixtureDir, callable $callable, $param = false) {
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

        $this->assertHtmlStringEqualsHtmlString($expectedHtml, $result);
    }

      /**
     * Provide tests for `testStripImages()`.
     * @return array
     */
    public function provideImageData(): array {
        $r = [
            'BBCode' => ['', '<img src="http://oracle.spawn/uploads/editor/7b/ojeblp0ylk7w.jpeg" alt="ojeblp0ylk7w.jpeg" class="embedImage-img importedEmbed-img" />'],
            'Html' => ['', '<img src="http://test.spawn/uploads/editor/te/k6rq7bu2jzub.jpeg" alt="" />'],
            'Rich' => ['', '<img src="http://test.spawn/uploads/editor/ng/yjf6b4r40ydk.jpeg" alt="" class="embedImage-img importedEmbed-img"></img>'],
        ];

        return $r;
    }

    /**
     * Provide tests for `TestTruncateWords()`.
     * @return array
     */
    public function provideTruncateWordsData(): array {
        $r = [
            'Test10Words' => ['10', '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi</p>', '<p>Veggies es bonus vobis, proinde vos postulo essum magis kohlrabi welsh onion daikon amaranth tatsoi tomatillo melon azuki bean garlic.</p><br><p>Gumbo beet greens corn soko endive gumbo gourd. Parsley shallot courgette tatsoi pea sprouts fava bean collard greens dandelion okra wakame tomato.</p>'],
            'Test2Words' => ['2', 'One dollar', 'One dollar'],
            'Test5Words' => ['4', 'One dollar and eighty-seven', 'One dollar and eighty-seven cents'],
        ];

        return $r;
    }
}
