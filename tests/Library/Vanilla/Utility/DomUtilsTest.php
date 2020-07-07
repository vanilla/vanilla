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

/**
 * Class for testing dom utility functions.
 */
class DomUtilsTest extends TestCase {
    use AssertsFixtureRenderingTrait;

    /**
     * Test truncating words.
     *
     * @param int|null $wordCount
     * @param string $html
     * @param string $expected
     * @dataProvider provideTrimWordsTests
     */
    public function testTrimWords(?int $wordCount, string $html, string $expected): void {
        $domDocument = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom = $domDocument->getDom();

        if (empty($wordCount)) {
            DomUtils::trimWords($dom);
        } else {
            DomUtils::trimWords($dom, $wordCount);
        }

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
            'Mixed nested' => [2, 'a <b>b</b> c', 'a <b>b</b>'],
            "Short html" => [4, 'a b', 'a b'],
            'Heavily nested' => [
                2,
                '<div><div><div><div>this</div> is a word</div></div> <b>okay?</b></div>',
                '<div><div><div><div>this </div> is</div></div></div>'
            ],
            'Test default wordCount' => [   //100
                null,
                'Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non vehicula',
                'Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non'
            ]
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

    /**
     * Test preg replace.
     *
     * @param int $expectedCount
     * @param string|string[] $patternText
     * @param string $input
     * @param int $expected
     * @dataProvider providePregReplaceCallbackTests
     */
    public function testPregReplaceCallback($expectedCount, string $patternText, string $input, string $expected): void {
        $domDocument = new HtmlDocument($input);
        $dom = $domDocument->getDom();
        $pattern = ['/'.$patternText.'/'];
        $count = DomUtils::pregReplaceCallback($dom, $pattern, function (array $matches): string {
            return '***';
        });
        $actual = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
        $this->assertSame($expectedCount, $count);
    }

    /**
     * Make sure that HTML in the callback doesn't corrupt the DOM.
     */
    public function testPregReplaceCallbackHtml(): void {
        $in = new HtmlDocument('<p>this is bad.</p>');

        DomUtils::pregReplaceCallback($in->getDom(), ['`bad`'], function (array $matches): string {
            return str_repeat('>', strlen($matches[0]));
        });
        $actual = $in->getInnerHtml();
        $expected = <<<EOT
<p>this is &gt;&gt;&gt;.</p>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Provide tests for `TestPregReplaceCallback()`.
     *
     * @return array
     */
    public function providePregReplaceCallbackTests(): array {
        $r = [
            'Testtext' => [
                1,
                'forbiddenword', 'test forbiddenword','test ***'
            ],
            'TestBrokenHtml' => [
                1,
                'forbiddenword', '<p>test forbiddenword</p></p>','<p>test ***</p></p>'
            ],
            'Testtext2' => [
                1,
                'forbiddenword','test forbiddenword test forbiddenword', 'test *** test ***'
            ],
            'PTag' => [1, 'forbiddenword', '<p>test forbiddenword</p>', '<p>test ***</p>'],
            'Nested' => [1, 'blockedword', '<div><div><div><b>blockedword test</b></div></div></div>', '<div><div><div><b>*** test</b></div></div></div>'],
            'Mixed nested' => [2, 'blocked word', 'a <b>test blocked word</b> test blocked word', 'a <b>test ***</b> test ***'],
            'aria-label' => [2,'forbiddenword', '<button aria-label="forbiddenword content" onclick="myDialog.close()">forbiddenword content</button>',
                '<button aria-label="*** content" onclick="myDialog.close()">*** content</button>'],
            'alt' => [1,
                'forbiddenword',
                '<img src="img_test.jpg" alt="forbiddenword image" width="100" height="100">',
                '<img src="img_test.jpg" alt="*** image" width="100" height="100">'],
            'emoji' => [1, '🤓','test 🤓', 'test ***'],
            'count' => [2, 'a+', '<p>a aaa is</p><p>aaa</p>', '<p>*** *** is</p><p>***</p>']
        ];
        return $r;
    }
}
