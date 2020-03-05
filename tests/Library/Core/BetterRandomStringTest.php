<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for betterRandomString().
 */

class BetterRandomStringTest extends TestCase {

    /**
     * Tests {@link betterRandomString()} against several scenarios.
     * Checks the length of the random string against the $length parameter
     * and that the string contains only the designated characters set in $characterOptions parameter.
     *
     * @param int $testLength The length of the string.
     * @param string $testCharacterOptions Character sets that are allowed in the string.
     * @dataProvider provideTestBetterRandomStringArrays
     */
    public function testBetterRandomString($testLength, $testCharacterOptions) {
        $characterClasses = [
            'A' => str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 1),
            'a' => str_split('abcdefghijklmnopqrstuvwxyz', 1),
            '0' => str_split('0123456789', 1),
            '!' => str_split('~!@#$^&*_+-', 1),
        ];

        $testCharacterOptionsArray = str_split($testCharacterOptions, 1);
        $validCharacters = [];
        foreach ($testCharacterOptionsArray as $validCharSet) {
            if (array_key_exists($validCharSet, $characterClasses)) {
                $validCharacters = array_merge($validCharacters, $characterClasses[$validCharSet]);
            }
        }
        $resultingString = betterRandomString($testLength, $testCharacterOptions);
        $this->assertCount($testLength, str_split($resultingString));

        $resultingStringArray = str_split($resultingString, 1);
        foreach ($resultingStringArray as $character) {
            $this->assertContains($character, $validCharacters);
        }
    }

    /**
     * Provide test data for {@link testBetterRandomString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestBetterRandomStringArrays() {
        $r = [
            'capsOnly' => [
                25,
                'A',
            ],
            'allLetters' => [
                25,
                'Aa',
            ],
            'lettersAndNumbers' => [
                25,
                'Aa0',
            ],
            'allChars' => [
                25,
                'Aa0!',
            ],
            'weakString' => [
                1,
                'a',
            ],
            'characterOptionsTooLong' => [
                25,
                'Aa0!8',
            ],
        ];

        return $r;
    }
}
