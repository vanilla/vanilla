<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validatePhoneNA().
 */

class ValidatePhoneNATest extends TestCase {

    /**
     * Test with empty string.
     */
    public function testValidatePhoneNAWithEmptyString() {
        $actual = validatePhoneNA('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with integers.
     */
    public function testValidatePhoneNAWithIntegers() {
        $actual = validatePhoneNA(12345678901);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with string.
     */
    public function testValidatePhoneNAWithString() {
        $actual = validatePhoneNA('12345678901');
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
