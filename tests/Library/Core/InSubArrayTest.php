<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for inSubArray().
 */
class InSubArrayTest extends TestCase {

    /**
     * Test {@link inSubArray()} against several scenarios.
     *
     * @param mixed $testNeedle The value to search for.
     * @param array $testHaystack The array to search.
     * @param bool $expected The expected result.
     * @dataProvider provideInSubArrayArrays
     */
    public function testInSubArray($testNeedle, array $testHaystack, bool $expected) {
        $actual = inSubArray($testNeedle, $testHaystack);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link inSubArray()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideInSubArrayArrays() {
        $r = [
            'valueNotInSubArray' => [
              'needle',
              ['needle', 'needle', ['noNeedle', 'noNeedle']],
                false,
            ],
            'boolInSubArray' => [
                false,
                [1, 'false', [false, 'string'], 'string'],
                true,
            ],
            'boolNotInSubArray' => [
                true,
                [1, 'string', true, [false, '']],
                false,
            ],
            'stringInSubArray' => [
                'string',
                [3, '3', ['string', false, 5], false],
                true,
            ],
            'stringNotInSubArray' => [
                'string',
                ['wrongString', 'anotherWrongString', 5, true, ['noString', 8]],
                false,
            ],
            'intInSubArray' => [
                1,
                [[1, 'string', false], 2, 'string', false],
                true,
            ],
            'intNotInSubArray' => [
                1,
                [2, 8, ['string', 5]],
                false,
            ],
            'arrayInSubArray' => [
                [1, 2, 3],
                [4, 'string', [1, 2, [1, 2, 3]], false],
                true,
            ],
            'arrayNotInSubArray' => [
                [1, 2, 3],
                [1, 2, [8, 9, [3, 4, 5]], 'string', true],
                false,
            ],
        ];

        return $r;
    }
}
