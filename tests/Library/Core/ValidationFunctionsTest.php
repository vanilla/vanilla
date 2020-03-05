<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validation functions.
 */
class ValidationFunctionsTest extends TestCase {

    /**
     * Assert a value is an instance of the Invalid class.
     *
     * @param mixed $value
     */
    protected function assertInvalid($value) {
        $this->assertInstanceOf(\Vanilla\Invalid::class, $value);
    }

    /**
     * Test validating a time-only string.
     *
     * @param mixed $value The time to validate.
     * @param bool $isValid Is this expected to be a valid time?
     * @dataProvider provideTimes
     */
    public function testValidateTime($value, $isValid) {
        $result = validateTime($value);
        if ($isValid) {
            $this->assertIsString('string', $value);
        } else {
            $this->assertInvalid($result);
        }
    }

    /**
     * Provides test time strings and whether or not they are valid.
     *
     * @return array
     */
    public function provideTimes() {
        return [
            ['12:00:00', true],
            ['12:00', true],
            ['12', false],
            ['24:00:00', true],
            ['60:00:00', false],
            ['24:60:00', false],
            ['24:00:60', false],
            ['24:00:59', true],
            ['24:59:00', true],
            ['00:59:59', true],
            ['1:00', true],
            [1, false],
            [true, false],
            [false, false],
            [null, false],
        ];
    }
}
