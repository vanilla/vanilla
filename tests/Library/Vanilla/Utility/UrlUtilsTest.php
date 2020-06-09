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
class UrlUtilsTest extends TestCase {

    /**
     * @var array saves $_SERVER values before the test.
     */
    private static $originalServer;

    /**
     * Set $_SERVER values that are required for http_build_url().
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::$originalServer = $_SERVER;
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['SERVER_NAME'] = 'vanilla.test';
        $_SERVER['REQUEST_URI'] = '/';
    }

    /**
     * Reset $_SERVER values.
     */
    public static function tearDownAfterClass(): void {
        parent::tearDownAfterClass();
        $_SERVER = self::$originalServer;
    }

    /**
     * Provide data for testing the testDomainAsAscii function.
     *
     * @return array of valid domains to test.
     */
    public function provideUnicodeDomains(): array {
        $result = [
            'Valid ASCII domain' => ['www.vanillaforums.com', 'http://www.vanillaforums.com'],
            'Valid Unicode domain' => ['http://www.goοgle.com/test', 'http://www.xn--gogle-sce.com/test'],
            'Valid ASCII domain with fragment' => ['https://www.google.com/path/to/page?query=string#fragment', 'https://www.google.com/path/to/page?query=string#fragment'],
            'Valid Unicode domain with fragment' => [
                'https://www.goοgle.com/path/to/page?query=string#fragment', 'https://www.xn--gogle-sce.com/path/to/page?query=string#fragment'
            ],
            'Valid url' => ['http://www.vanillaforums.com', 'http://www.vanillaforums.com'],
            'Valid punycoded url' => ['xn--gogle-sce.com', 'http://xn--gogle-sce.com'],
        ];
        return $result;
    }

    /**
     * Test the domainAsAscii() function.
     *
     * @param string $domain Test domain.
     * @param string $punyEncoded Domain converted to IDNA ASCII.
     * @dataProvider provideUnicodeDomains
     */
    public function testDomainAsAscii($domain, $punyEncoded) {
        $result = UrlUtils::domainAsAscii($domain);
        $this->assertEquals($result, $punyEncoded);
    }

    /**
     * Test the domainAsAscii() function using a domain with invalid character.
     */
    public function testInvalidDomainAsAscii() {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain Invalid.');

        UrlUtils::domainAsAscii('//goo�gle.com/');
    }

    /**
     * Provide data for testing the replaceQuery method.
     *
     * @return array|array[]
     */
    public function provideQueryReplacements(): array {
        $result = [
            "Replace elements" => [
                "foo=bar&hello=world&a=1",
                ["foo" => "world", "hello" => "bar"],
                "foo=world&hello=bar&a=1",
            ],
            "Remove elements" => [
                "foo=bar&hello=world&a=1",
                ["foo" => null, "a" => null],
                "hello=world",
            ],
            "Remove all elements" => [
                "foo=bar&hello=world&a=1",
                ["foo" => null, "hello" => null, "a" => null],
                "",
            ],
            "Empty changeset" => [
                "foo=bar&hello=world&a=1",
                [],
                "foo=bar&hello=world&a=1",
            ],
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
    public function testReplaceQuery(string $query, array $replace, string $expected): void {
        $uri = Http::createFromString("https://example.com")->withQuery($query);

        $result = UrlUtils::replaceQuery($uri, $replace);
        $this->assertSame($expected, $result->getQuery());
    }

    /**
     * Test path encoding/decoding.
     */
    public function testEncodeDecodePath(): void {
        $encoded = 'profile/Fran%23k';
        $decoded = 'profile/Fran#k';

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
    public function testNormalizeEncoding(string $url, string $expected): void {
        $fixed = UrlUtils::normalizeEncoding($url);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Provide URL fix tests.
     *
     * @return array
     */
    public function provideFixUrlTests(): array {
        $r = [
            ['https://de.wikipedia.org/wiki/Prüfsumme', 'https://de.wikipedia.org/wiki/Pr%C3%BCfsumme'],
            ['https://de.wikipedia.org/wiki/Prüfsümme', 'https://de.wikipedia.org/wiki/Pr%C3%BCfs%C3%BCmme'],
            ['https://de.wikipedia.org/wiki/Pr%C3%BCfsumme', 'https://de.wikipedia.org/wiki/Pr%C3%BCfsumme'],
            ['https://de.wikipedia.org/wiki/Pr%C3%BCfsümme', 'https://de.wikipedia.org/wiki/Pr%C3%BCfs%C3%BCmme'],
            ['https://example.com', 'https://example.com'],
            ['https://example.com/foo.html', 'https://example.com/foo.html'],
            ['https://example.com/foo.html?q=a', 'https://example.com/foo.html?q=a'],
            ['https://example.com/foo.html?q=ü', 'https://example.com/foo.html?q=%C3%BC'],
        ];
        return array_column($r, null, 0);
    }
}
