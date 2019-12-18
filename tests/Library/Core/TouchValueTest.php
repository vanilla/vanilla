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
 * Tests for touchValue().
 */

class TouchValueTest extends TestCase {

    /**
     * Tests (@link touchValue()} against several scenarios.
     *
     * @param string $testKey The key or property name of the value.
     * @param mixed $testCollection The array or object to set.
     * @param mixed $testDefault The value to set.
     * @param mixed $expected The expected result.
     * @dataProvider provideTestTouchValueArraysArray
     * @dataProvider provideTestTouchValueArraysObject
     */
    public function testTouchValue($testKey, &$testCollection, $testDefault, $expected) {
        $actual = touchValue($testKey, $testCollection, $testDefault);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testTouchValue()}.
     */

    /**
     * Test data where collection is an array.
     */
    public function provideTestTouchValueArraysArray() {
        $r = [
            'keyAlreadyExists' => [
                'a',
                ['a' => 'a', 'b' => 'b'],
                'b',
                'a',
            ],
            'keyDoesNotExist' => [
                'a',
                ['b' => 'b'],
                'a',
                'a',
            ],
        ];

        return $r;
    }

    /**
     * Test data where collection is an object.
     */
    public function provideTestTouchValueArraysObject() {
        $testObject = new Tuple('a', 'b');
        $r = [
            'propertyAlreadyExists' => [
                'a',
                $testObject,
                'b',
                'a',
            ],
            'propertyDoesNotExist' => [
                'c',
                $testObject,
                'c',
                'c',
            ],
        ];

        return $r;
    }
}
