<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validatePhoneInt()
 */

class ValidatePhoneIntTest extends TestCase {

    /**
     * Test with empty string.
     */
    public function testValidatePhoneIntWithEmptyString() {
        $actual = validatePhoneInt('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with string.
     */
    public function testValidatePhoneIntWithIntegers() {
        $actual = validatePhoneInt('+12345678901234');
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
