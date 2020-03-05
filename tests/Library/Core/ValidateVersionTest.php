<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateVersion().
 */

class ValidateVersionTest extends TestCase {

    /**
     * Test valid string.
     */
    public function testValidateVersionWithValidString() {
        $actual = validateVersion('4.2-r5');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with empty string.
     */
    public function testValidateVersionWithEmptyString() {
        $actual = validateVersion("");
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with invalid string (doesn't match pattern).
     */
    public function testValidateVersionWithInvalidString() {
        $actual = validateVersion('foo');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
