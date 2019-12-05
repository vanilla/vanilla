<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;


use PHPUnit\Framework\TestCase;


/**
 * Tests for arrayKeyExistsITests().
 */
class ArrayKeyExistsITest extends TestCase {

    /**
     * Test {@link arrayKeyExistsI()} against several scenarios.
     *
     * @param string|int $testKey Key to search for.
     * @param array $testSearch Array to search.
     * @param bool $expected Expected result.
     * @dataProvider provideKeyExistsIArrays
     */
    public function testArrayKeyExistsI($testKey, array $testSearch, bool $expected) {
        $actual = arrayKeyExistsI($testKey, $testSearch);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link arrayKeyExistsI()}
     *
     * @return array Returns an array of test data.
     */
    public function provideKeyExistsIArrays() {
        $r = [
            'strKeyExistsSameCase' => [
                'foo',
                ['foo' => 'foo', 'bar' => 'bar'],
                true,
            ],
            'strKeyExistsDiffCase' => [
                'bAr',
                ['foo' => 'foo', 'BaR' => 'bar'],
                true,
            ],
            'strKeyDoesNotExist' => [
                'barfoo',
                ['foo' => 'foo', 'BaR' => 'bar'],
                false,
            ],
            'intKeyExists' => [
                0,
                ['0' => 'foo'],
                true,
            ],
            'intKeyDoesNotExist' => [
                0,
                [1 => 'foo'],
                false,
            ]
        ];

        return $r;
    }
}
