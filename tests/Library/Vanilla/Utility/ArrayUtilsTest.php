<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Fixtures\DumbArray;
use VanillaTests\Fixtures\IterableArray;

/**
 * Tests for the `ArrayUtils` class.
 */
class ArrayUtilsTest extends TestCase {
    /**
     * Test `ArrayUtils::walkRecursiveArray()` with array updates.
     *
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider  provideWalkArrayUpdateTests
     */
    public function testWalkArrayUpdates($input, $expected): void {
        $actual = $input;

        ArrayUtils::walkRecursiveArray($actual, function (&$a, $path) {
            $a['p'] = $path;
        });

        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide some array walk array tests that add a value to the array.
     *
     * @return array
     */
    public function provideWalkArrayUpdateTests(): array {
        $r = [
            'basic' => [['a' => 'b'], ['a' => 'b', 'p' => []]],
            'recursive' => [['a' => ['a' => 'b']], ['a' => ['a' => 'b', 'p' => ['a']], 'p' => []]],
            'object' => [new \ArrayObject(['a' => 'b']), new \ArrayObject(['a' => 'b', 'p' => []])],
            'recursive object' => [
                new \ArrayObject(['a' => ['a' => 'b']]),
                new \ArrayObject(['a' => ['a' => 'b', 'p' => ['a']], 'p' => []]),
            ],
            'recursive dumb' => [
                ['a' => new DumbArray(['a' => 'b'])],
                ['a' => new DumbArray(['a' => 'b', 'p' => ['a']]), 'p' => []],
            ],
        ];

        return $r;
    }

    /**
     * Calling `ArrayUtils::walkRecursiveArray()` without an array is an exception.
     */
    public function testWalkArrayRecursiveNotArray(): void {
        $s = "hello";

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("expects argument 1 to be an array or array-like object.");
        ArrayUtils::walkRecursiveArray($s, function ($a) {
            // Do nothing.
        });
    }

    /**
     * Test happy paths for `ArrayUtils::keys()`.
     *
     * @param mixed $arr
     * @dataProvider provideKeysTests
     */
    public function testKeys($arr): void {
        $expected = ['a', 'b', 'c'];
        $actual = ArrayUtils::keys($arr);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide various test data for `testKeys()`.
     *
     * @return array
     */
    public function provideKeysTests(): array {
        $arr = ['a' => 1, 'b' => 1, 'c' => 1];

        $r = [
            'array' => [$arr],
            'ArrayObject' => [new \ArrayObject($arr)],
            'custom' => [new IterableArray($arr)]
        ];

        return $r;
    }

    /**
     * The `ArrayUtils::keys()` method should throw an exception when it doesn't get an array.
     */
    public function testKeysException() : void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expects argument 1 to be an array or array-like object');
        ArrayUtils::keys("foo");
    }
}
