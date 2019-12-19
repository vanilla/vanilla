<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for implodeAssoc().
 */

class ImplodeAssocTest extends TestCase {

    /**
     * Test {@link implodeAssoc()} against several scenarios.
     *
     * @param string $testKeyGlue The glue between the keys and values.
     * @param string $testElementGlue The glue between array elements.
     * @param array $testArray The array to implode.
     * @param string $expected The expected result.
     * @dataProvider provideImplodeAssocTestArrays
     */
    public function testImplodeAssoc($testKeyGlue, $testElementGlue, $testArray, $expected) {
        $actual = implodeAssoc($testKeyGlue, $testElementGlue, $testArray);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link implodeAssoc()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideImplodeAssocTestArrays() {
        $r = [
            'emptyArray' => [
                '>',
                '+',
                [],
                '',
            ],
            'oneToOneArray' => [
                '>',
                '+',
                ['one' => 'thingOne', 'two' => 'thingTwo'],
                'one>thingOne+two>thingTwo',
            ],
            'nonAssocArray' => [
                '>',
                '+',
                ['one', 'two', 'three'],
                '0>one+1>two+2>three',
            ],
            'numValsArray' => [
                '>',
                '+',
                [0, 1, 2],
                '0>0+1>1+2>2',
            ],
        ];

        return $r;
    }
}
