<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateEmail().
 */

class ValidateEmailTest extends TestCase {

    /**
     * Test with email address.
     */
    public function testWithEmailAddress() {
        $actual = validateEmail('dick@example.com');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with no email address.
     */
    public function testNoEmailAddress() {
        $actual = validateEmail('no-address-here');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with array including email address
     */
    public function testWithBool() {
        $actual = validateEmail(false);
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
