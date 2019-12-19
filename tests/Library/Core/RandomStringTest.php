<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Test randomString().
 */

class RandomStringTest extends TestCase {

    /**
     * Tests {@link randomString()} against several scenarios.
     *
     * @param int $testLength The length of the string to generate.
     * @param string $testCharacters The allowed characters in the string.
     * @dataProvider provideTestRandomStringArrays
     */
    public function testRandomString($testLength, $testCharacters) {
        $characterClasses = [
            'A' => str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 1),
            'a' => str_split('abcdefghijklmnopqrstuvwxyz', 1),
            '0' => str_split('0123456789', 1),
            '!' => str_split('~!@#$^&*_+-', 1),
        ];

        $testCharacterOptionsArray = str_split($testCharacters, 1);
        $validCharacters = [];
        foreach ($testCharacterOptionsArray as $validCharSet) {
            if (array_key_exists($validCharSet, $characterClasses)) {
                $validCharacters = array_merge($validCharacters, $characterClasses[$validCharSet]);
            }
        }
        $resultingString = randomString($testLength, $testCharacters);
        $this->assertCount($testLength, str_split($resultingString));

        $resultingStringArray = str_split($resultingString, 1);
        foreach ($resultingStringArray as $character) {
            $this->assertContains($character, $validCharacters);
        }
    }

    /**
     * Provide test data for {@link testRandomString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestRandomStringArrays() {
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
