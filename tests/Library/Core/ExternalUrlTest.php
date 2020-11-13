<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\BootstrapTestCase;

/**
 * Tests for the `externalUrl()` function.
 */
class ExternalUrlTest extends BootstrapTestCase {
    /**
     * Assert a call to `externalUrl()`.
     *
     * @param string $expected
     * @param string $urlFormat
     * @param string $path
     */
    public function assertExternalUrl(string $expected, string $urlFormat, string $path): void {
        $this->runWithConfig(['Garden.ExternalUrlFormat' => $urlFormat], function () use ($expected, $path) {
            $actual = externalUrl($path);
            $this->assertSame($expected, $actual);
        });
    }

    /**
     * Test basic external URL use-cases.
     *
     * @param string $expected
     * @param string $urlFormat
     * @param string $path
     * @dataProvider provideExternalUrls
     */
    public function testExternalUrl(string $expected, string $urlFormat, string $path): void {
        $this->assertExternalUrl($expected, $urlFormat, $path);
    }

    /**
     * Data provider.
     *
     * @return \string[][]
     */
    public function provideExternalUrls(): array {
        $r = [
            'embedded' => ['https://example.com/forum#/foo/bar?baz=qux', 'https://example.com/forum#/%s', '/foo/bar?baz=qux'],
            'custom route' => ['https://example.com/foo/bar?baz=qux', 'https://example.com/%s', '/foo/bar?baz=qux'],
            'full url' => ['https://example.com/foo/bar?baz=qux', 'https://example.com/%s', 'https://example.com/foo/bar?baz=qux'],
            'full schemaless url' => ['http://example.com/foo/bar?baz=qux', 'https://example.com/%s', '//example.com/foo/bar?baz=qux'],
            'qs path' => ['https://example.com?p=/foo/bar&baz=qux', 'https://example.com?p=/%s', '/foo/bar?baz=qux'],
            'qs path and hash' => ['https://example.com?p=123#/foo/bar?baz=qux', 'https://example.com?p=123#/%s', '/foo/bar?baz=qux'],
        ];
        return $r;
    }
}
