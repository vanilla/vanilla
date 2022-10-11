<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\StringUtils;
use VanillaTests\Fixtures\Tuple;

/**
 * Tests for the `StringUtils` class.
 */
class StringUtilsTest extends TestCase
{
    /**
     * Test a basic happy path for `jsonEncodeChecked()`.
     */
    public function testJsonEncodeHappy(): void
    {
        $str = StringUtils::jsonEncodeChecked(123);
        $this->assertSame("123", $str);
    }

    /**
     * Test `jsonEncodeChecked()` exceptions.
     *
     * @param mixed $in
     * @param string $message
     * @dataProvider provideJsonEncodeExceptions
     */
    public function testJsonEncodeExceptions($in, $message): void
    {
        $this->expectExceptionMessage($message);
        $str = StringUtils::jsonEncodeChecked($in);
    }

    /**
     * @return array
     */
    public function provideJsonEncodeExceptions(): array
    {
        $rec = new \stdClass();
        $rec->r = $rec;

        $r = [[$rec, "recursive references"], [substr("🐶🐶", -1), "Malformed UTF-8"], [NAN, "NAN or INF"]];

        return array_column($r, null, 1);
    }

    /**
     * Test `StringUtils::substringLeftTrim()`.
     *
     * @param string $str
     * @param bool $caseInsensitive
     * @param string $expected
     * @dataProvider provideLeftTrimTests
     */
    public function testSubstringLeftTrim(string $str, bool $caseInsensitive, string $expected): void
    {
        $actual = StringUtils::substringLeftTrim($str, "foo", $caseInsensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide trim tests.
     *
     * @return array
     */
    public function provideLeftTrimTests(): array
    {
        $r = [
            "happy" => ["foobar", false, "bar"],
            "shorter" => ["f", false, "f"],
            "not" => ["bazbar", false, "bazbar"],
            "case insensitive" => ["FOObar", true, "bar"],
        ];

        return $r;
    }

    /**
     * Test `StringUtils::substringRightTrim()`.
     *
     * @param string $str
     * @param bool $caseInsensitive
     * @param string $expected
     * @dataProvider provideRightTrimTests
     */
    public function testSubstringRightTrim(string $str, bool $caseInsensitive, string $expected): void
    {
        $actual = StringUtils::substringRightTrim($str, "foo", $caseInsensitive);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide trim tests.
     *
     * @return array
     */
    public function provideRightTrimTests(): array
    {
        $r = [
            "happy" => ["barfoo", false, "bar"],
            "shorter" => ["f", false, "f"],
            "not" => ["bazbar", false, "bazbar"],
            "case insensitive" => ["barFOO", true, "bar"],
        ];

        return $r;
    }

    /**
     * Test `StringUtils::labelize()`.
     *
     * @param string $in
     * @param string $expected
     * @dataProvider provideLabelizeTests
     */
    public function testLabelize(string $in, string $expected): void
    {
        $actual = StringUtils::labelize($in);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function provideLabelizeTests(): array
    {
        $r = [
            ["fooBar", "Foo Bar"],
            ["foo  bar", "Foo Bar"],
            ["fooID", "Foo ID"],
            ["fooURL", "Foo URL"],
            ["foo_bar", "Foo Bar"],
            ["foo-bar", "Foo Bar"],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test the contains() method.
     */
    public function testContains()
    {
        $this->assertTrue(StringUtils::contains("string 1", "ring"));
        $this->assertTrue(StringUtils::contains("string 1", "string 1"));
        $this->assertFalse(StringUtils::contains("string 1", "string 12"));
        $this->assertFalse(StringUtils::contains("string 1", ""));
    }

    /**
     * Test that non-utf-8 chars are propely removed and that the whitespace at the extremity of the string is properly removed.
     *
     * @param $text
     * @param $expected
     * @dataProvider provideStripUnicodeData
     */
    public function testStripUnicodeWhitespace($text, $expected)
    {
        $result = StringUtils::stripUnicodeWhitespace($text);
        $this->assertEquals($expected, $result);
    }

    /**
     * DataProvider for testProfileFieldValueProcessing.
     *
     * @return array
     */
    public function provideStripUnicodeData()
    {
        $r = [
            ["this\u{0009}Is\u{3000}A\u{2029}Test", "thisIsATest"],
            [" thisIsATest ", "thisIsATest"],
            ["En français", "En français"],
            ["ช่องโปรไฟล์", "ช่องโปรไฟล์"],
        ];
        return $r;
    }
}
