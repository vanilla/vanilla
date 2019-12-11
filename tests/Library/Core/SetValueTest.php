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
 * Tests for setValue().
 */

class SetValueTest extends TestCase {

    /**
     * Tests {@link setValue()} against several scenarios.
     *
     * @param string $testNeedle The key or property name of the value.
     * @param mixed $testHaystack The array or object to set.
     * @param mixed $testValue The value to set.
     * @param mixed $expected The expected result.
     * @dataProvider provideTestSetValueArrays
     */
    public function testSetValue($testNeedle, $testHaystack, $testValue, $expected) {
        setValue($testNeedle, $testHaystack, $testValue);
        $actual = $testHaystack[$testNeedle];
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link setValue()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSetValueArrays() {
        $r = [
          'arraySet' => [
              'one',
              ['one' => 'two', 'two' => 'two'],
              'one',
              'one',
          ],
//          'objectSet' => [
////              "a",
////              new Tuple('a', 'b'),
////              'x',
////              'x',
//          ],
          'arrayNoNeedle' => [
              'a',
              ['b' => 'b', 'c' => 'c'],
              'a',
              'a',
          ],
//          'objectNoNeedle' => [
//              'c',
//              new \stdClass('a', 'b'),
//              'c',
//              'c',
//          ],
        ];

        return $r;
    }
}
