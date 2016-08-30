<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

/**
 * Test array functions.
 */
class ArrayFunctionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * Test {@link flattenArray()}.
     *
     * @param array $array The input array to test.
     * @param array $expected The expected flattened array.
     * @dataProvider provideFlattenArrayTests
     */
    public function testFlattenArray($array, $expected) {
        $value = flattenArray('.', $array);
        $this->assertEquals($expected, $value);
    }

    /**
     * Provide test data for {@link flattenArray()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideFlattenArrayTests() {
        $r = [
            'threeDeep' => [['a' => ['b' => ['c' => 'foo']]], ['a.b.c' => 'foo']],
            'emptyArray' => [[], []],
            'emptyArrayElement' => [['a' => 1, 'b' => []], ['a' => 1]]
        ];

        return $r;
    }
}
