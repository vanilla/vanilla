<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Utility\HtmlUtils;

/**
 * Tests for the `HtmlUtils` class.
 */
final class HtmlUtilsTest extends TestCase {

    /**
     * Tests for `HtmlUtils::classNames()`
     *
     * @param array $args
     * @param string $expected
     * @dataProvider provideClassNameTests
     */
    public function testClassNames(array $args, string $expected): void {
        $actual = HtmlUtils::classNames(...$args);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide tests for `testClassNames()`.
     * @return array
     */
    public function provideClassNameTests(): array {
        $r = [
            [[''], ''],
            [['foo', 'bar'], 'foo bar'],
            [['foo', null, 'bar'], 'foo bar'],
            [['foo', '', 'bar'], 'foo bar'],
        ];
        return $r;
    }

    /**
     * Test get classes.
     *
     * @param \DOMElement $element
     * @param array $expected
     *
     * @dataProvider provideGetClasses
     */
    public function testGetClasses(\DOMElement $element, array $expected) {
        $this->assertEquals($expected, HtmlUtils::getClasses($element));
    }

    /**
     * @return HtmlDocument
     */
    private function getTestHtmlDoc(): HtmlDocument {
        $html = '<div id="1" class="class1 class2 class1"></div><div id="2" class="class1"></div>';
        return new HtmlDocument($html);
    }

    /**
     * @return array
     */
    public function provideGetClasses(): array {
        $doc = $this->getTestHtmlDoc();
        return [
            [ $doc->queryXPath('//*[@id="1"]')->item(0), ['class1', 'class2'] ],
            [ $doc->queryXPath('//*[@id="2"]')->item(0), ['class1'] ]
        ];
    }

    /**
     * Test hasClass.
     *
     * @param \DOMElement $element
     * @param string $class
     * @param bool $expected
     *
     * @dataProvider provideHasClass
     */
    public function testHasClass(\DOMElement $element, string $class, bool $expected) {
        $this->assertEquals($expected, HtmlUtils::hasClass($element, $class));
    }

    /**
     * @return array
     */
    public function provideHasClass(): array {
        $doc = $this->getTestHtmlDoc();
        return [
            [ $doc->queryXPath('//*[@id="1"]')->item(0), 'class1', true ],
            [ $doc->queryXPath('//*[@id="1"]')->item(0), 'class2', true ],
            [ $doc->queryXPath('//*[@id="1"]')->item(0), 'class3', false ],
            [ $doc->queryXPath('//*[@id="1"]')->item(0), 'class', false ],
        ];
    }

    /**
     * Test appendClass.
     *
     * @param \DOMElement $element
     * @param string $class
     * @param array $expected
     *
     * @dataProvider provideAppendClass
     */
    public function testAppendClass(\DOMElement $element, string $class, array $expected) {
        HtmlUtils::appendClass($element, $class);
        $this->assertEquals($expected, HtmlUtils::getClasses($element));
    }

    /**
     * @return array
     */
    public function provideAppendClass(): array {
        return [
            'add existing class' => [
                $this->getTestHtmlDoc()->queryXPath('//*[@id="1"]')->item(0),
                'class1',
                ['class1', 'class2'],
            ],
            'add new class' => [
                $this->getTestHtmlDoc()->queryXPath('//*[@id="1"]')->item(0),
                'class3',
                ['class1', 'class2', 'class3'],
            ],
        ];
    }

    /**
     * Formatting tags when the argument isn't supplied is a notice.
     */
    public function testFormatTagsInvalidArgNotice(): void {
        $actual = @HtmlUtils::formatTags('Hello <0/>');
        $this->assertSame('Hello ', $actual);

        $this->expectNotice();
        $this->expectNoticeMessage('<0/>');
        HtmlUtils::formatTags('Hello <0/>');
    }

    /**
     * Test various format tag formats.
     *
     * @param string $format
     * @param string $expected
     * @dataProvider provideFormatTagFormats
     */
    public function testFormatTagsFormats(string $format, string $expected): void {
        $args = [
            'b',
            ['img', 'src' => '//example.com/foo.png'],
            ['a', 'href' => 'http://site.com'],
            'world'
        ];

        $actual = HtmlUtils::formatTags($format, ...$args);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide various formats for `testFormatTagsFormats()`.
     *
     * @return array
     */
    public function provideFormatTagFormats(): array {
        $r = [
            ['test', 'test'],
            ['Hello <1 /> world!', 'Hello <img src="//example.com/foo.png" /> world!'],
            ['This is <0>important</0>', 'This is <b>important</b>'],
            ['Visit <2>our site</2> for help.', 'Visit <a href="http://site.com">our site</a> for help.'],
            ["<0>a</0>\n<0>b</0>", "<b>a</b>\n<b>b</b>"],
            ['Hello <3/>', 'Hello world'],
            ['<b>a</b>', '<b>a</b>'],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Tests for `HtmlUtils::attributes()`.
     *
     * @param array $attributes
     * @param string $expected
     * @dataProvider provideAttributesTests
     */
    public function testAttributes(array $attributes, string $expected): void {
        $actual = HtmlUtils::attributes($attributes);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide tests for `testAttributes()`.
     *
     * @return array
     */
    public function provideAttributesTests(): array {
        $r = [
            'empty' => [[], ''],
            'one' => [['a' => 'b'], ' a="b"'],
            'two' => [['a' => 'b', 'c' => 'd'], ' a="b" c="d"'],
            'bool true' => [['type' => 'checkbox', 'checked' => true], ' type="checkbox" checked'],
            'bool false' => [['type' => 'checkbox', 'checked' => false], ' type="checkbox"'],
            'json data' => [['data-foo' => ['a' => 'b']], ' data-foo="{&quot;a&quot;:&quot;b&quot;}"'],
            'unicode json' => [['data-foo' => ['a' => 'ぁ']], ' data-foo="{&quot;a&quot;:&quot;ぁ&quot;}"'],
        ];
        return $r;
    }


}
