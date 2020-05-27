<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ObjectUtils;

/**
 * Tests for the `ObjectUtils` class.
 */
class ObjectUtilsTest extends TestCase {
    /**
     * Test the happy path fot `ObjectUtils::hydrate()`.
     */
    public function testHydrateHappy(): void {
        $a = new class {
            public $a;

            /**
             * Setter.
             *
             * @param mixed $a
             */
            public function setB($a) {
                $this->a = $a;
            }
        };

        $r = ObjectUtils::hydrate($a, ['a' => 'a']);
        $this->assertSame('a', $a->a);
        $this->assertSame('a', $r->a);

        $r = ObjectUtils::hydrate($a, ['b' => 'b']);
        $this->assertSame('b', $a->a);
        $this->assertSame('b', $r->a);
    }

    /**
     * You can't set a non-public property.
     */
    public function testHydratePrivate(): void {
        $a = new class {
            private $a;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(403);
        ObjectUtils::hydrate($a, ['a' => 'a']);
    }

    /**
     * You can't set a non-public property.
     */
    public function testHydrateMissing(): void {
        $a = new class {
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(400);
        ObjectUtils::hydrate($a, ['a' => 'a']);
    }

    /**
     * Test `ObjectUtils::with()`.
     */
    public function testWith(): void {
        $a = new class {
            public $a = 'a';
        };

        $b = ObjectUtils::with($a, ['a' => 'b']);
        $this->assertSame(get_class($a), get_class($b));
        $this->assertSame('a', $a->a);
        $this->assertSame('b', $b->a);
    }
}
