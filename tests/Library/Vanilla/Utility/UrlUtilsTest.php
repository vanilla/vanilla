<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use League\Uri\Http;
use Vanilla\Utility\UrlUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class UrlUtilsTest Tests domainAsAscii() function.
 *
 * @package VanillaTests\Library\Vanilla\Utility
 */
class UrlUtilsTest extends TestCase
{
    /**
     * @var array saves $_SERVER values before the test.
     */
    private static $originalServer;

    /**
     * Set $_SERVER values that are required for http_build_url().
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$originalServer = $_SERVER;
        $_SERVER["SERVER_PORT"] = "80";
        $_SERVER["SERVER_NAME"] = "vanilla.test";
        $_SERVER["REQUEST_URI"] = "/";
    }

    /**
     * Reset $_SERVER values.
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $_SERVER = self::$originalServer;
    }

    /**
     * Provide data for testing the testDomainAsAscii function.
     *
     * @return iterable valid URLs to test.
     * @link http://homoglyphs.net Homoglyph generator
     * @link https://www.charset.org/punycode Punycode converter
     */
    public function provideUnicodeDomains(): iterable
    {
        yield "Valid ASCII domain without scheme" => [
            "url" => "www.vanillaforums.com",
            "punyEncoded" => "http://www.vanillaforums.com",
        ];
        yield "Valid Unicode domain" => [
            "url" => "http://www.goοgle.com/test",
            "punyEncoded" => "http://www.xn--gogle-sce.com/test",
        ];
        yield "Valid ASCII host with ASCII username, no password" => [
            "url" => "https://guest:@www.vanillaforums.com",
            "punyEncoded" => "https://guest:@www.vanillaforums.com",
        ];
        yield "Valid ASCII host with ASCII username and password" => [
            "url" => "https://guest:guest@www.vanillaforums.com",
            "punyEncoded" => "https://guest:guest@www.vanillaforums.com",
        ];
        yield "Valid ASCII host with Unicode userinfo" => [
            "url" => "http://уｏｕ:ѡоｎ@android-winners-central.com/free/phone",
            "punyEncoded" => "http://xn--ou-tmc:xn--n-0tb8h@android-winners-central.com/free/phone",
        ];
        yield "Valid Unicode host with Unicode userinfo" => [
            "url" => "http://ｆｒｅе:ⅰｐｈοｎｅ@аррӏе.com/you/won",
            "punyEncoded" => "http://xn--fre-tdd:xn--iphne-tce@xn--80ak6aa92e.com/you/won",
        ];
        yield "Valid punycoded host with punycoded userinfo" => [
            "url" => "http://xn--fre-tdd:xn--iphne-tce@xn--80ak6aa92e.com/you/won",
            "punyEncoded" => "http://xn--fre-tdd:xn--iphne-tce@xn--80ak6aa92e.com/you/won",
        ];
        yield "Valid ASCII-only URL with fragment" => [
            "url" => "https://www.google.com/path/to/page?query=string#fragment",
            "punyEncoded" => "https://www.google.com/path/to/page?query=string#fragment",
        ];
        yield "Valid Unicode domain with fragment" => [
            "url" => "https://www.goοgle.com/path/to/page?query=string#fragment",
            "punyEncoded" => "https://www.xn--gogle-sce.com/path/to/page?query=string#fragment",
        ];
        yield "Valid ASCII-only url" => [
            "url" => "http://www.vanillaforums.com",
            "punyEncoded" => "http://www.vanillaforums.com",
        ];
        yield "Valid punycoded url without scheme" => [
            "url" => "xn--gogle-sce.com",
            "punyEncoded" => "http://xn--gogle-sce.com",
        ];
    }

    /**
     * Test the domainAsAscii() function.
     *
     * @param string $url Test domain.
     * @param string $punyEncoded Domain converted to IDNA ASCII.
     * @dataProvider provideUnicodeDomains
     */
    public function testDomainAsAscii($url, $punyEncoded)
    {
        $result = UrlUtils::domainAsAscii($url);
        $this->assertEquals($result, $punyEncoded);
    }

    /**
     * Provide invalid URLs to domainAsAscii
     *
     * @return iterable
     */
    public function provideInvalidUrls(): iterable
    {
        yield "parse_url returns false" => [
            "url" => "http://user@:80",
            "msg" => "Url Invalid",
        ];
        yield "Whitespace in host position" => [
            "url" => "http://www.ɡооɡⅼе.ϲоⅿ\:@%20",
            "msg" => "Url Invalid",
        ];
        yield "Invalid character in host" => [
            "url" => "//goo�gle.com/",
            "msg" => "Domain Invalid.",
        ];
        yield "Invalid character in username" => [
            "url" => "http://ｆｒ�ｅе:ⅰｐｈοｎｅ@аррӏе.com/you/won",
            "msg" => "Username Invalid.",
        ];
        yield "Invalid character in password" => [
            "url" => "http://ｆｒｅе:ⅰｐｈοｎ�ｅ@аррӏе.com/you/won",
            "msg" => "Password Invalid.",
        ];
    }

    /**
     * Test that domainAsAscii returns an InvalidArgumentException when provided invalid characters within URL
     *
     * @param string $url
     * @param string $msg
     * @return void
     * @dataProvider provideInvalidUrls
     */
    public function testDomainAsAsciiThrowsInvalidArgumentException(string $url, string $msg): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($msg);
        $_ = UrlUtils::domainAsAscii($url);
    }

    /**
     * Provide data for testing the replaceQuery method.
     *
     * @return array|array[]
     */
    public function provideQueryReplacements(): array
    {
        $result = [
            "Replace elements" => [
                "foo=bar&hello=world&a=1",
                ["foo" => "world", "hello" => "bar"],
                "foo=world&hello=bar&a=1",
            ],
            "Remove elements" => ["foo=bar&hello=world&a=1", ["foo" => null, "a" => null], "hello=world"],
            "Remove all elements" => ["foo=bar&hello=world&a=1", ["foo" => null, "hello" => null, "a" => null], ""],
            "Empty changeset" => ["foo=bar&hello=world&a=1", [], "foo=bar&hello=world&a=1"],
        ];
        return $result;
    }

    /**
     * Verify updating URI query elements.
     *
     * @param string $query
     * @param array $replace
     * @param string $expected
     * @dataProvider provideQueryReplacements
     */
    public function testReplaceQuery(string $query, array $replace, string $expected): void
    {
        $uri = Http::createFromString("https://example.com")->withQuery($query);

        $result = UrlUtils::replaceQuery($uri, $replace);
        $this->assertSame($expected, $result->getQuery());
    }

    /**
     * Test path encoding/decoding.
     */
    public function testEncodeDecodePath(): void
    {
        $encoded = "profile/Fran%23k";
        $decoded = "profile/Fran#k";

        $this->assertSame($encoded, UrlUtils::encodePath($decoded));
        $this->assertSame($decoded, UrlUtils::decodePath($encoded));
    }

    /**
     * Fix URLs with a mix of encoded and non-encoded UTF-8 in their paths.
     *
     * @param string $url
     * @param string $expected
     * @dataProvider provideFixUrlTests
     */
    public function testNormalizeEncoding(string $url, string $expected): void
    {
        $fixed = (string) UrlUtils::normalizeEncoding(Http::createFromString($url));
        $this->assertSame($expected, $fixed);
    }

    /**
     * Provide URL fix tests.
     *
     * @return array
     */
    public function provideFixUrlTests(): array
    {
        $r = [
            ["https://de.wikipedia.org/wiki/Prüfsumme", "https://de.wikipedia.org/wiki/Pr%C3%BCfsumme"],
            ["https://de.wikipedia.org/wiki/Prüfsümme", "https://de.wikipedia.org/wiki/Pr%C3%BCfs%C3%BCmme"],
            ["https://de.wikipedia.org/wiki/Pr%C3%BCfsumme", "https://de.wikipedia.org/wiki/Pr%C3%BCfsumme"],
            ["https://de.wikipedia.org/wiki/Pr%C3%BCfsümme", "https://de.wikipedia.org/wiki/Pr%C3%BCfs%C3%BCmme"],
            ["https://example.com", "https://example.com"],
            ["https://example.com/foo.html", "https://example.com/foo.html"],
            ["https://example.com/foo.html?q=a", "https://example.com/foo.html?q=a"],
            ["https://example.com/foo.html?q=ü", "https://example.com/foo.html?q=%C3%BC"],
        ];
        return array_column($r, null, 0);
    }
}
