<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;


class GeneralFunctionsTest extends \PHPUnit_Framework_TestCase {

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
        if ($expectException && PHP_VERSION_ID < 50500) {
            if (version_compare(\PHPUnit_Runner_Version::id(), '5.0', '<')) {
                $this->setExpectedException('Exception');
            } else {
                $this->expectException('Exception');
            }
        }

        jsonEncodeChecked($data);
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
}
