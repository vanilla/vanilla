<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateEnum().
 */

class ValidateEnumTest extends TestCase {

    /**
     * Test validateEnum() with valid value.
     */
    public function testValidateEnumValid() {
        $testObject = new \stdClass();
        $testObject->Enum = [1, 2, 3];
        $actual = validateEnum(1, $testObject);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test validateEnum() with invalid value.
     */
    public function testValidateEnumInvalid() {
        $testObject = new \stdClass();
        $testObject->Enum = [1, 2, 3];
        $testObject->AllowNull = false;
        $actual = validateEnum(4, $testObject);
        $expected = false;
        $this->assertSame($expected, $actual);
    }
}
