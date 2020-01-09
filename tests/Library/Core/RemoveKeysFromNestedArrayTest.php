<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Tuple;

/**
 * Tests for removeKeysFromNestedArray().
 */

class RemoveKeysFromNestedArrayTest extends TestCase {

    /**
     * Test {@link removeKeysFromNestedArray()} against several scenarios.
     *
     * @param array $testArray The input array.
     * @param array[string|int] $testMatches An array of keys to remove.
     * @param array $expected The expected result.
     * @dataProvider provideTestRemoveKeysFromNestedArrayArrays
     */
    public function testRemoveKeysFromNestedArray($testArray, $testMatches, $expected) {
        $actual = removeKeysFromNestedArray($testArray, $testMatches);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * Test {@link removeKeysFromNestedArray()} on an object.
     */
    public function testRemoveKeysFromNestedArrayFromObject() {
        $expected = [2, new Tuple('a', 'b'), 5];
        unset($expected[1]->b);
        $actual = removeKeysFromNestedArray([2, new Tuple('a', 'b'), 5], ['b']);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide test data for {@link removeKeysFromNestedArray()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestRemoveKeysFromNestedArrayArrays() {
        $r = [
            'removeStrKeys' => [
                [2, ['key1' => 0, "key2" => 1, "key3" => 2], 1],
                ['key1', 'key2', 'key3'],
                [2, [], 1],
            ],
            'removeNumKeys' => [
                [2, [5 => 1, 6 => 2, 7 => 3], 4],
                [6, 7],
                [2, [1], 4],
            ],
        ];

        return $r;
    }
}
