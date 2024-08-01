<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\StringUtils;
use VanillaTests\Library\Vanilla\CsvProviderTrait;

/**
 * Tests for the `StringUtils` class.
 */
class StringUtilsTest extends TestCase
{
    use CsvProviderTrait;

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
     * @throws \Exception
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
            ["This\nis\non\nmultiple\nlines", "This\nis\non\nmultiple\nlines"],
        ];
        return $r;
    }

    /**
     * Test that array are properly encoded into CSVs.
     *
     * @param $toEncode
     * @param $expected
     * @dataProvider provideCsvData
     */
    public function testCsvEncoding($toEncode, $expected)
    {
        $result = StringUtils::encodeCSV($toEncode);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that an empty array return an error.
     */
    public function testCsvEmptyEncoding()
    {
        $this->expectExceptionMessage("Value to convert into CSV is empty.");
        $this->expectExceptionCode(500);
        StringUtils::encodeCSV([]);
    }

    /**
     * @param string $input
     * @param array|null $expected
     * @param string|null $expectedExceptionMessage
     *
     * @dataProvider provideDecodeJwt
     */
    public function testDecodeJwt(string $input, ?array $expected, string $expectedExceptionMessage = null)
    {
        if ($expectedExceptionMessage != null) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }
        $actual = StringUtils::decodeJwtPayload($input);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return \Generator
     */
    public function provideDecodeJwt()
    {
        yield "working" => [
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE2Njg5NzY0MjcsImlhdCI6MTY2NjM4NDQyNywic2lkIjoiYWEwNDM1YjY1MTdmMTFlZGIwYjU2MjgxNjk5ZDFmZDQiLCJzdWIiOjl9.Lb4NKZzod9YDuJGOHn5h15qb8w6UZ9HTaG-crd6KIH0",
            [
                "exp" => 1668976427,
                "iat" => 1666384427,
                "sid" => "aa0435b6517f11edb0b56281699d1fd4",
                "sub" => 9,
            ],
        ];

        yield "wrong number of segments" => [
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.Lb4NKZzod9YDuJGOHn5h15qb8w6UZ9HTaG-crd6KIH0",
            null,
            "Wrong number of segments",
        ];

        $badJson = base64_encode("{asdf}");
        yield "Bad json" => [
            "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.$badJson.Lb4NKZzod9YDuJGOHn5h15qb8w6UZ9HTaG-crd6KIH0",
            null,
            "json_decode error: Syntax error",
        ];
    }

    /**
     * Test page number parsing.
     *
     * @param string $path
     * @param int $expectedNumber
     *
     * @dataProvider providePageNumbers
     */
    public function testPageNumber(string $path, int $expectedNumber)
    {
        $this->assertEquals($expectedNumber, StringUtils::parsePageNumberFromPath($path));
    }

    /**
     * @return array
     */
    public function providePageNumbers(): array
    {
        return [["/test/test/path/p642/p12", 12], ["/test/p6", 6], ["asdf", 1], ['asdf$#%$^%&*&^%$#!/', 1]];
    }

    /**
     * Test the UrlCode is extracted from a given path.
     *
     * @dataProvider provideParseUrlCodeFromPath
     * @return void
     */
    public function testParseUrlCodeFromPath($expected, $url)
    {
        $this->assertEquals($expected, StringUtils::parseUrlCodeFromPath($url));
    }

    /**
     * Provide data for testParseUrlCodeFromPath().
     * `/categories` isn't part of the url.
     *
     * @return array
     */
    public function provideParseUrlCodeFromPath(): array
    {
        // `/categories`isn't included.
        return [
            ["some-category-slug", "/some-category-slug"],
            ["some-category-slug", "/some-category-slug/1"],
            ["some1categoryslug", "/some1CategorySlug?param1=one&param2=two"],
            ["somecategoryslug", "/SomeCategorySlug/social-groups?param=value#anchor"],
            ["somec@tegoryslug", "/somec@tegoryslug/test"],
            ["somecategoryslug!", "/somecategoryslug!/test"],
            ["some-(category)-slug", "/some-(category)-slug/test"],
        ];
    }

    /**
     * Test the IDs are extracted from a given path
     *
     * @return void
     */
    public function testParseIDFromPath()
    {
        $pathWithoutID = "/";
        $pathWithNumericID = "/100";
        $pathWithStringID = "/some-string";
        $pathWithIDAndSlug = "/100/some-slug";

        $this->assertEquals("100", StringUtils::parseIDFromPath($pathWithIDAndSlug, "\/"));
        $this->assertEquals("100", StringUtils::parseIDFromPath($pathWithNumericID, "\/"));
        $this->assertEquals(null, StringUtils::parseIDFromPath($pathWithStringID, "\/"));
        $this->assertEquals(null, StringUtils::parseIDFromPath($pathWithoutID, "\/"));
        $this->assertEquals(null, StringUtils::parseIDFromPath(null));
    }
}
