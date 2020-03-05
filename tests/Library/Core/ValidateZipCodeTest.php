<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateZipCode().
 */

class ValidateZipCodeTest extends TestCase {

    /**
     * Test with empty string.
     */
    public function testValidateZipCodeWithEmptyString() {
        $actual = validateZipCode('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with 5-digit ints.
     */
    public function testValidateZipCodeWithFiveDigitIntegers() {
        $actual = validateZipCode(22207);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with 9-digit string.
     */
    public function testValidateZipCodeWithNineDigitString() {
        $actual = validateZipCode('22207-1234');
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
