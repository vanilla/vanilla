<?php
/**
 * @author Patrick Kelly <patrick.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for arrayTranslate().
 */
class ArrayTranslateTest extends TestCase {

    /**
     * Test  {@link arrayTranslate()} against several scenarios.
     *
     * @param array $testArray Array to be translated.
     * @param array $testMap The map of what the array keys will be.
     * @param array $addRemaining Add keys from the array map to the array even if they do not exist on the array.
     * @param array $expected
     * @dataProvider provideTranslateableArrays
     */
    public function testArrayTranslate(array $testArray, array $testMap, bool $addRemaining, array $expected) {
        $value = arrayTranslate($testArray, $testMap, $addRemaining);
        $this->assertSame($expected, $value);
    }

    /**
     * Provide test data for {@link arrayTranslate()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTranslateableArrays() {
        $r = [
            'simpleAssocAdd' => [
                ['badKey' => 'a.b'],
                ['badKey' => 'fixedKey'],
                1,
                ['fixedKey' => 'a.b']
            ],
            'longAssocAdd' => [
                ['badKey' => 'a.b', 'anotherIndex' => 'b.c'],
                ['badKey' => 'fixedKey'],
                1,
                ['fixedKey' => 'a.b', 'anotherIndex' => 'b.c']
            ],
            'simpleAssocNoAdd' => [
                ['badKey' => 'a.b'],
                ['badKey' => 'fixedKey'],
                0,
                ['fixedKey' => 'a.b']
            ],
            'longAssocNoAdd' => [
                ['badKey' => 'a.b', 'anotherIndex' => 'b.c'],
                ['badKey' => 'fixedKey'],
                0,
                ['fixedKey' => 'a.b']
            ],
        ];

        return $r;
    }
}
