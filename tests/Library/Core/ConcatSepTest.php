<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for concatSep().
 */
class ConcatSepTest extends TestCase {

    /**
     * Test {@link concatSep()} against several scenarios.
     *
     * @param string $expected Expected result
     * @param string $testSep The separator string to insert between concatenated strings.
     * @param string|array $args The string or strings to concatenate.
     *  -The arguments after $testSep can be an indefinite number of strings.
     *  -Alternately, the function can take 1 additional argument that is an array of strings.
     *  -$testStr2 can actually be an array. If it is a string, the function will look for further parameters.
     * @dataProvider provideConcatSepArrays
     */
    public function testConcatSep(string $expected, string $testSep, ...$args) {
        $actual = concatSep($testSep, ...$args);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link concatSep()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideConcatSepArrays() {
        $r = [

            'concatTwoStrings' => [
                'str1+str2',
                '+',
                'str1',
                'str2',
            ],
            'concatOneStrAndOneArray' => [
                'str1+str2+str3',
                '+',
                'str1',
                ['str2', 'str3'],
            ],
            'concatThreeStrings' => [
                'str1+str2+str3',
                '+',
                'str1',
                'str2',
                'str3',
            ],
            'concatStringArrayString' => [
                'str1+str2+str3',
                '+',
                'str1',
                ['str2', 'str3'],
                'str4',
            ],
            'oneEmptyString' => [
                'str2',
                '+',
                '',
                'str2',
            ],
        ];

        return $r;
    }
}
