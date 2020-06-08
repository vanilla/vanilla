<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * Utility methods for testing an object.
 */
trait ObjectTestTrait {
    /**
     * Assert that a wither works.
     *
     * @param string $property
     * @param mixed $value
     */
    public function assertWith(string $property, $value): void {
        $e2 = $this->object->{"with$property"}($value);
        $this->assertNotSame($this->object, $e2, "Withers are must return a new object.");
        $this->assertSame($value, $e2->{"get$property"}());
        $this->assertNotSame($value, $this->object->{"get$property"}(), "Wither must not alter the original object.");
    }

    /**
     * Assert that a setter works.
     *
     * @param string $property
     * @param mixed $value
     */
    public function assertSet(string $property, $value): void {
        $this->object->{"set$property"}($value);
        $this->assertSame($value, $this->object->{"get$property"}());
    }
}
