<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Test array functions.
 */
class ArrayFunctionsTest extends SharedBootstrapTestCase {

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
            'emptyArrayElement' => [['a' => 1, 'b' => []], ['a' => 1]],
            'realWorldExample' => [
                [
                    'a' => [
                        'b' => ['c' => 'foo'],
                        'd' => ['e' => 'bar'],
                    ],
                ],
                [
                    'a.b.c' => 'foo',
                    'a.d.e' => 'bar'
                ]
            ],
        ];

        return $r;
    }

    /**
     * Test {@link testArrayPathExists()}.
     *
     * @dataProvider provideArrayPathExistsTests
     *
     * @param $keys
     * @param $array
     * @param $expectedResult
     * @param $expectedValue
     *
     * @param $expected
     */
    public function testArrayPathExists($keys, $array, $expectedResult, $expectedValue) {
        $this->assertEquals($expectedResult, arrayPathExists($keys, $array, $value));
        $this->assertEquals($expectedValue, $value);
    }

    /**
     * Provide test data for {@link arrayPathExists()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideArrayPathExistsTests() {
        return [
            'noKeys' => [
                [],
                ['a' => ['aa' => null]],
                false,
                null,
            ],
            'noKeysWithNullTypeIndexOnFirstLevel' => [
                [],
                [null => true],
                false,
                null,
            ],
            'nullStringKeyWithNullTypeIndex' => [
                ['a', 'null'],
                ['a' => [null => null]],
                false,
                null,
            ],
            'nestedIsNotAnArray' => [
                ['a', 'b', 'c'],
                ['a' => ['b' => true, 'd' => ['c']]],
                false,
                null,
            ],
            'nullTypeKeyWithNullTypeIndex' => [
                ['a', null],
                ['a' => [null => null]],
                true,
                null,
            ],
            'nullStringKeyWithNullStringIndex' => [
                ['a', 'null'],
                ['a' => ['null' => 'Something']],
                true,
                'Something',
            ],
            'deeplyNested' => [
                ['a', 'aa', 'aaa', 'aaaa'],
                ['a' => ['aa' => ['aaa' => ['aaaa' => []]]]],
                true,
                [],
            ],
        ];
    }

    /**
     * Test {@link unflattenArray()}.
     *
     * @param array $array The input array to test.
     * @param array $expected The expected flattened array.
     * @dataProvider provideUnflattenArrayTests
     */
    public function testUnflattenArray($array, $expected) {
        $value = unflattenArray('.', $array);
        $this->assertEquals($expected, $value);
    }

    /**
     * Provide test data for {@link unflattenArray()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideUnflattenArrayTests() {
        $r = [
            'threeDeep' => [['a.b.c' => 'foo'], ['a' => ['b' => ['c' => 'foo']]]],
            'emptyArray' => [[], []],
            'realWorldExample' => [
                [
                    'a.b.c' => 'foo',
                    'a.d.e' => 'bar'
                ],
                [
                    'a' => [
                        'b' => ['c' => 'foo'],
                        'd' => ['e' => 'bar'],
                    ],
                ],
            ],
        ];

        return $r;
    }

}
