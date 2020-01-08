<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for check_utf8().
 */
class CheckUtf8Test extends TestCase {

    /**
     * Test {@link check_utf8()} against several scenarios.
     *
     * @param string $testStr The string to check.
     * @param bool $expected The expected result.
     * @dataProvider provideCheckUtf8Arrays
     */
    public function testCheckUtf8($testStr, $expected) {
        $actual = check_utf8($testStr, $expected);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link check_utf8()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideCheckUtf8Arrays() {
        $r = [
            'notUtf8' => [
                chr(0xC0),
                false,
            ],
            'validUtfSting' => [
                'stress',
                true,
            ],
            'higherThan247' => [
                chr(248),
                false,
            ],
            'higherThan239' => [
                chr(240),
                false,
            ],
            'higherThan223' => [
                chr(224),
                false,
            ],
            'higherThan191' => [
                chr(192),
                false,
            ],
            'outOfRange' => [
                chr(-1),
                false,
            ],
        ];

        return $r;
    }
}
