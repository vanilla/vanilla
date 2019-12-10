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
 * Tests for getValueR().
 */

class GetValueRTest extends TestCase {

    /**
     * Test {@link getValueR()} against several scenarios.
     *
     * @param string $testKey The key or property name of the value.
     * @param mixed $testCollection The array or object to search.
     * @param mixed $testDefault The value to return if the key does not exist.
     * @param mixed $expected The expected result.
     * @dataProvider provideGetValueRArrays
     */
    public function testGetValueR($testKey, $testCollection, $testDefault, $expected) {
        $actual = getValueR($testKey, $testCollection, $testDefault);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link getValueR()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideGetValueRArrays() {
        $r = [
            'flatArray' => [
                1,
                ['zero', 'one'],
                false,
                'one',
            ],
            'nestedArray' => [
                'outer.inner',
                ['outer' => ['inner' => 'expectedResult']],
                false,
                'expectedResult',
            ],
            'flatObject' => [
                'a',
                new Tuple('a', 'b'),
                false,
                'a'
            ],
            'nestedObjectProperty' => [
                'a.a',
                new Tuple(new Tuple('a', 'b'), 'b'),
                false,
                'a',
            ],
            'propertyNotPresent' => [
                'c',
                new Tuple('a', 'b'),
                false,
                false,
            ],
        ];

        return $r;
    }
}
