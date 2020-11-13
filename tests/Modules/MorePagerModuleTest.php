<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Modules;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;

/**
 * Tests for the `MorePagerModule` class.
 */
class MorePagerModuleTest extends TestCase {
    use BootstrapTrait;

    /**
     * The more pager has its own way of formatting URLs.
     *
     * @param string $url
     * @param string $expected
     * @dataProvider provideFormatUrlTests
     */
    public function testFormatUrl(string $url, string $expected): void {
        $actual = \MorePagerModule::formatUrl($url, 10, 10, 101);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function provideFormatUrlTests(): array {
        $r = [
            ['%1$s to %2$s', '11 to 20'],
            ['%1$s to %2$s of %3$s', '11 to 20 of 101'],
            ['{from} to {to}', '11 to 20'],
            ['{from} to {to} of {count}', '11 to 20 of 101'],
        ];

        return array_column($r, null, 0);
    }
}
