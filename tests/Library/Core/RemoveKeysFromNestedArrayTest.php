<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

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
     * Provide test data for {@link removeKeysFromNestedArray()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestRemoveKeysFromNestedArrayArrays() {
        $r = [
            'removeStrKeys' => [
                [2, ['key1' => 0, "key2" => 1, "key3" => 2], 1],
                ['key1', 'key2', 'key3'],
                [2, [0, 1, 2], 1],
            ],
        ];

        return $r;
    }
}
