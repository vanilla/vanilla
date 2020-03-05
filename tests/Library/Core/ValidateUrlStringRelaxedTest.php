<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateUrlStringRelaxed().
 */

class ValidateUrlStringRelaxedTest extends TestCase {

    /**
     * Test with valid string.
     */
    public function testWithValidString() {
        $actual = validateUrlStringRelaxed('valid-string');
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test with invalid string.
     */
    public function testWithInvalidString() {
        $actual = validateUrlStringRelaxed('not>a/valid\string');
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
