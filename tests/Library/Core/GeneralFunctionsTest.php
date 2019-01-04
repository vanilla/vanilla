<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

class GeneralFunctionsTest extends SharedBootstrapTestCase {

    /**
     * Test {@link urlMatch()}.
     *
     * @param string $pattern The URL pattern.
     * @param string $url The URL.
     * @param bool $result The expected result.
     * @dataProvider provideUrlMatchTests
     */
    public function testUrlMatch($pattern, $url, $result) {
        $this->assertSame($result, urlMatch($pattern, $url));
    }

    /**
     * Test {@link jsonEncodeChecked()}.
     *
     * @param mixed $data
     * @param bool $expectException
     * @dataProvider provideJsonEncodeCheckedTests
     */
    public function testJsonEncodeChecked($data, $expectException) {
        if ($expectException) {
            $this->expectException('Exception');
        }

        $encodedData = jsonEncodeChecked($data);

        $this->assertNotFalse($encodedData);
    }

    /**
     * Provide test data for {@link testJsonEncodeChecked}
     */
    public function provideJsonEncodeCheckedTests() {
        $exampleIPv6Packed = inet_pton('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        return [
            [$exampleIPv6Packed, true],
            [['IPAddress' => $exampleIPv6Packed], true],
            [['Alpha' => 'One', 'Beta' => 'Two', 'Charlie' => 'Three'], false]
        ];
    }

    /**
     * Provide test data for {@link testUrlMatch()}.
     */
    public function provideUrlMatchTests() {
        $r = [
            'empty pattern' => ['', 'http://example.com', false],
            'equals' => ['foo.com', 'http://foo.com', true],
            'equals 2' => ['foo.com', 'http://foo.com/', true],
            'wildcard path' => ['foo.com/bar/*', 'http://foo.com/bar', true],
            'wildcard path 2' => ['foo.com/bar/*', 'http://foo.com/bar/', true],
            'wildcard path 3' => ['foo.com/bar/*', 'http://foo.com/bar/baz', true],
            'wildcard path 4' => ['foo.com/bar/*', 'http://foo.com/bart', false],
            'empty path pattern' => ['foo.com', 'https://foo.com/bar', true],
            'empty path' => ['foo.com/bar', 'http://foo.com', false],
            'scheme mismatch' => ['https://foo.com', 'http://foo.com', false],
            'domain mismatch' => ['foo.com', 'http://google.com', false],
            'subdomain' => ['foo.com', 'http://www.foo.com', false],
            'subdomain wildcard' => ['*.foo.com', 'http://www.foo.com', true],
            'subdomain wildcard 2' => ['*.foo.com', 'http://foo.com', true],
            'bad substring domain' => ['*.foo.com', 'http://xssfoo.com', false],
            'bad substring domain 2' => ['foo.com', 'http://xssfoo.com', false],
        ];
        return $r;
    }

    /**
     * Test some {@link isTrustedDomain()} from the config.
     *
     * Note that since isTrustedDomain caches a copy of the trusted domains this unit test might fail if it isn't the only one.
     *
     * @param string $url The URL to test.
     * @param bool $expected The expected result from {@link isTrustedDomain()}.
     * @dataProvider provideTrustedDomainConfigs
     */
    public function testIsTrustedDomainConfig($url, $expected) {
        $trustedDomains = [
            '*.foo.com',
            ' https://bar.com',
            'https://baz.com/entry/*',
            'example.*',
            'domain.com'
        ];
        saveToConfig('Garden.TrustedDomains', implode("\n", $trustedDomains), false);

        $r = isTrustedDomain($url);
        $this->assertSame($expected, $r);
    }

    /**
     * Provide tests for {@link testIsTrustedDomainConfig()}.
     *
     * @return array Returns a data provider.
     */
    public function provideTrustedDomainConfigs() {
        $r = [
            'domain url' => ['http://domain.com', true],
            'domain domain' => ['domain.com', true],
            'wildcard domain 1' => ['www.foo.com', true],
            'wildcard domain 2' => ['https://www.foo.com', true],
            'wildcard domain 3' => ['example.evildomain.com', false],
            'scheme mismatch' => ['http://bar.com', false],
            'path mismatch' => ['https://bar.com/another', false],
            'wildcard path 1' => ['https://baz.com/entry', true],
            'wildcard path 2' => ['https://baz.com/entry/signin', true]
        ];

        return $r;
    }

    /**
     * Test the **absoluteSource()** function.
     *
     * @param string $srcPath The source path.
     * @param string $url The full URL that the source path is on.
     * @param string $expected The expected absolute source result.
     * @dataProvider provideAbsoluteSourceTests
     */
    public function testAbsoluteSource(string $srcPath, string $url, string $expected) {
        $actual = absoluteSource($srcPath, $url);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide tests for **testAbsoluteSource()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideAbsoluteSourceTests() {
        $r = [
            'root' => ['/foo', 'http://ex.com/bar', 'http://ex.com/foo'],
            'relative' => ['bar', 'http://ex.com/foo', 'http://ex.com/foo/bar'],
            'relative slash' => ['bar', 'http://ex.com/foo/', 'http://ex.com/foo/bar'],
            'scheme' => ['https://ex.com', 'http://ex.com', 'https://ex.com'],
            'schema-less' => ['//ex.com', 'https://baz.com', 'https://ex.com'],
            'bad scheme' => ['bad://ex.com', 'http://ex.com', ''],
            'bad scheme 2' => ['foo', 'bad://ex.com', ''],
            '..' => ['../foo', 'http://ex.com/bar/baz', 'http://ex.com/bar/foo'],
            '.. 2' => ['../foo', 'http://ex.com/bar/baz/', 'http://ex.com/bar/foo'],
            '../..' => ['../../foo', 'http://ex.com/bar/baz', 'http://ex.com/foo'],
        ];

        return $r;
    }
}
