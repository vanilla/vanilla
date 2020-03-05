<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateDate().
 */

class ValidateDateTest extends TestCase {

    /**
     * Test with empty value.
     */
    public function testEmptyValue() {
        $actual = validateDate('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with valid format.
     */
    public function testValidFormat() {
        $actual = validateDate('1980-06-17 20:00:00');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with valid form, but invalid values.
     */
    public function testValidFormBadValues() {
        $actual = validateDate('1980-06-17 20:00:70');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with invalid format.
     */
    public function testInvalidFormat() {
        $actual = validateDate('06-17-1980');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
