<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Tuple;

/**
 * Tests for walkAllRecursive().
 */

class WalkAllRecursiveTest extends TestCase {

    /**
     * Tests {@link walkAllRecursive()} against various scenarios.
     */
    public function testBasic() {
        $testInput = ['a' => ['a', 'b'], 'b' => ['a', 'b']];
        $testCallback = function (&$value) {
            if ($value === 'a') {
                $value = 'b';
            }
        };
        $expected = ['a' => ['b', 'b'], 'b' => ['b', 'b']];
        walkAllRecursive($testInput, $testCallback);
        $this->assertSame($expected, $testInput);
    }

    /**
     * Test for infinite recursion.
     */
    public function testInfiniteRecursion() {
        $testInput = new Tuple('a', 'b');
        $testInput->a = $testInput;
        $cb = function ($value) {
        };
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(500);
        walkAllRecursive($testInput, $cb);
    }
}
