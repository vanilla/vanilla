<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for inArrayI().
 */
class InArrayITest extends TestCase {

    /**
     * Test {@link inArrayI()} against several scenarios.
     *
     * @param mixed $testNeedle The array value to search for.
     * @param array $testHaystack The array to search.
     * @param mixed $expected The expected result.
     * @dataProvider provideInArrayIArrays
     */
    public function testInArrayI($testNeedle, $testHaystack, $expected) {
        $actual = inArrayI($testNeedle, $testHaystack);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link inArrayI()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideInArrayIArrays() {
        $r = [
            'findNumber' => [
                1,
                [2, 3, 1],
                true,
            ],
            'findDifferentCaseString' => [
                'STRING',
                ['string', 'notString'],
                true,
            ],
            'stringIsNotThere' => [
                'sTrInG',
                ['notString', false, 0],
                false,
            ]
        ];

        return $r;
    }
}
