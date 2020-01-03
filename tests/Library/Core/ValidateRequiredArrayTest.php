<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateRequiredArray().
 */

class ValidateRequiredArrayTest extends TestCase {

    /**
     * Test with empty array.
     */
    public function testWithEmptyArray() {
        $actual = validateRequiredArray([]);
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with non-empty array.
     */
    public function testWithNonEmptyArray() {
        $actual = validateRequiredArray([false]);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with string.
     */
    public function testWithString() {
        $actual = validateRequiredArray('not-an-array');
        $expected = false;
        $this->assertSame($actual, $expected);
    }
}
