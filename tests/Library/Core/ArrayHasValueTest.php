<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;


use PHPUnit\Framework\TestCase;


/**
 * Tests for arrayHasValue().
 */
 class ArrayHasValueTest extends TestCase {

     /**
      * Test {@link arrayHasValue()} against several scenarios.
      *
      * @param array $testArray Array to search.
      * @param $testValue Value to search for.
      * @param bool $expected Expected result.
      * @dataProvider provideHasValueArrays
      */
     public function testArrayHasValue(array $testArray, $testValue, bool $expected) {
         $actual = arrayHasValue($testArray, $testValue);
         $this->assertSame($expected, $actual);
     }

     /**
      * Provide test data for {@link arrayHasValue()}.
      *
      * @return array Returns an array of test data.
      */
     public function provideHasValueArrays() {
         $r = [
             'doesHaveValueStr' => [
                 ['0' => 'x', '1' => 'y', '2' => 'z'],
                 'x',
                 true,
             ],
             'doesHaveValueInt' => [
                 ['0' => 1, '1' => 2, '2' => 3],
                 1,
                 true,
             ],
             'doesNotHaveValueStr' => [
                 ['0' => 'x', '1' => 'y', '2' => 'z'],
                 'b',
                 false,
             ],
             'doesNotHaveValueInt' => [
                 ['0' => 1, '1' => 2, '2' => 3],
                 5,
                 false,
             ],
             'hasValueNestedArray' => [
                 ['0' => 'x', ['1' => 'y', '2' => 'z']],
                 'z',
                 true,
             ],
             'doesNotHaveValueNestedArray' => [
                 ['0' => 'x', ['1' => 'y', '2' => 'z']],
                 'b',
                 false,
             ]
         ];

         return $r;
     }
}
