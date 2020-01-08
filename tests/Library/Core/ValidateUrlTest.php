<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateUrl().
 */

class ValidateUrlTest extends TestCase {

    /**
     * Test with empty string.
     */
    public function testValidateUrlEmptyString() {
        $actual = validateUrl('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with valid string.
     */
    public function testValidateUrlWithValidString() {
        $actual = validateUrl('http://example.com');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with invalid string.
     */
    public function testValidateUrlWithInvalidString() {
        $actual = validateUrl('http:///example.com');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
