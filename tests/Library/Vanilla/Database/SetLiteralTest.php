<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Database;

use PHPUnit\Framework\TestCase;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Database\SetLiterals\MinMax;

/**
 * Basic object access for the various `SetLiteral` classes.
 */
class SetLiteralTest extends TestCase {
    /**
     * Test `Increment::getAmount()`.
     */
    public function testIncrementAccessors(): void {
        $inc = new Increment(123);
        $this->assertSame(123, $inc->getAmount());
    }

    /**
     * Test the accessors for the `MinMax` class.
     */
    public function testMinMaxAccessors(): void {
        $min = new MinMax(MinMax::OP_MIN, 123);
        $this->assertSame(MinMax::OP_MIN, $min->getOp());
        $this->assertSame(123, $min->getValue());

        $max = new MinMax(MinMax::OP_MAX, 234);
        $this->assertSame(MinMax::OP_MAX, $max->getOp());
        $this->assertSame(234, $max->getValue());
    }

    /**
     * The `MinMax` class must have on of "min" or "max".
     */
    public function testInvalidMinMax(): void {
        $this->expectException(\InvalidArgumentException::class);
        $invalid = new MinMax('foo', 123);
    }
}
