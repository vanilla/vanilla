<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateOneOrMoreArrayItemRequired().
 */

class ValidateOneOrMoreArrayItemRequiredTest extends TestCase {

    /**
     * Test on an empty array.
     */
    public function testValidateOneOrMoreArrayItemRequiredEmpty() {
        $actual = validateOneOrMoreArrayItemRequired([]);
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with string.
     */
    public function testValidateOneOrMoreArrayItemRequiredString() {
        $actual = validateOneOrMoreArrayItemRequired('foo');
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with non-empty array.
     */
    public function testValidateOneOrMoreArrayItemRequiredNonEmptyArray() {
        $actual = validateOneOrMoreArrayItemRequired([false]);
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
