<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateWebAddress().
 */

class ValidateWebAddressTest extends TestCase {

    /**
     * Test with empty string.
     */
    public function testWithEmptyString() {
        $actual = validateWebAddress('');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with web address.
     */
    public function testWithWebAddress() {
        $actual = validateWebAddress('http://www.example.com');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test without web address.
     */
    public function testWithoutWebAddress() {
        $actual = validateWebAddress('this is not a web address at all');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
