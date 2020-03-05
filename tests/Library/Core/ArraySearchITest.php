<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for arraySearchI().
 */

class ArraySearchTestI extends TestCase {

    /**
     * Test {@link arraySearchI()} against several scenarios.
     *
     * @param string $testValue Value to find in array.
     * @param array $testSearch Array to search for $testValue.
     * @param string|int $expected Expected result.
     * @dataProvider provideSearchTestIArrays
     */
    public function testArraySearchI($testValue, array $testSearch, $expected) {
        $actual = arraySearchI($testValue, $testSearch);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link arraySearchI()}
     *
     * @return array Returns an array of test data.
     */
    public function provideSearchTestIArrays() {
        $r = [
            'hasValueSameCase' => [
                'foo',
                ['key1' => 'foo', 'key2' => 'bar'],
                'key1',
            ],
            'hasValueDiffCase' => [
                'FoO',
                ['key1' => 'fOo', 'key2' => 'bar'],
                'key1',
            ],
            'hasValueKeyInt' => [
                'foO',
                [0 => 'FOo', 1 => 'bar'],
                0,
            ],
            'doesNotHaveValue' => [
                'boo',
                ['key1' => 'foo', 'key2' => 'bar'],
                false,
            ]
        ];

        return $r;
    }
}
