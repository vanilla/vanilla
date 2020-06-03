<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
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
     * Formatting tags when the argument isn't supplied is a notice.
     */
    public function testFormatTagsInvalidArgNotice(): void {
        $actual = HtmlUtils::formatTags('Hello <0/>');
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
