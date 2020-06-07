<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use ArrayAccess;
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
     * Verify escaping reserved characters in key.
     *
     * @param string $key
     * @param string $expected
     * @dataProvider provideEscapeKeyTests
     */
    public function testEscapeKey(string $key, string $expected): void {
        $actual = ArrayUtils::escapeKey($key);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing the escapeKey method.
     *
     * @return array
     */
    public function provideEscapeKeyTests(): array {
        $result = [
            "basic" => ["foo.bar", "foo\\.bar"],
        ];

        return $result;
    }

    /**
     * Test getting an array element by its path.
     *
     * @param string $path
     * @param array|ArrayAccess $array
     * @param mixed $expected
     * @dataProvider  provideGetByPathTests
     */
    public function testGetByPath(string $path, $array, $expected): void {
        $actual = ArrayUtils::getByPath($path, $array);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing path resolution.
     *
     * @return array
     */
    public function provideGetByPathTests(): array {
        $result = [
            "simple path" => [
                "foo",
                ["foo" => "Hello world."],
                "Hello world.",
            ],
            "complex path" => [
                "a.b.c",
                [
                    "a" => [
                        "b" => ["c" => 123],
                    ]
                ],
                123,
            ],
            "unknown path" => [
                "foo.bar",
                ["foo" => "xyz"],
                null
            ],
            "unknown key" => [
                "foo.bar",
                ["foo" => ["xyz" => "Hello world."]],
                null,
            ],
            "escaped path" => [
                "foo.b\.ar.baz",
                [
                    "foo" => [
                        "b.ar" => ["baz" => "Hello world."],
                    ],
                ],
                "Hello world."
            ],
            "array object" => [
                "w.x.y",
                new \ArrayObject([
                    "w" => [
                        "x" => ["y" => "Hello world."],
                    ]
                ]),
                "Hello world.",
            ]
        ];
        return $result;
    }

    /**
     * Verify testIsAssociative method.
     *
     * @param array|ArrayAccess $array
     * @param bool $expected
     * @dataProvider provideIsAssociativeTests
     */
    public function testIsAssociative($array, bool $expected): void {
        $actual = ArrayUtils::isAssociative($array);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for the isAssociative method.
     *
     * @return array
     */
    public function provideIsAssociativeTests(): array {
        $result = [
            "associative" => [["foo" => "bar"], true],
            "indexed" => [["Hello world."], false],
            "mixed" => [["foo" => "bar", "Hello world."], true],
            "empty" => [[], false],
            "ArrayObject" => [new \ArrayObject(["foo" => "bar"]), true],
        ];

        return $result;
    }

    /**
     * Verify setByPath method.
     *
     * @param mixed $value
     * @param string $path
     * @param array|ArrayAccess $array
     * @param array|ArrayAccess $expected
     * @dataProvider provideSetByPathTests
     */
    public function testSetByPath($value, string $path, $array, $expected): void {
        $actual = ArrayUtils::setByPath($value, $path, $array);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for the setByPath method.
     *
     * @return array
     */
    public function provideSetByPathTests(): array {
        $result = [
            "simple" => [
                "Hello world.",
                "foo.bar.baz",
                ["foo" => ["bar" => []]],
                ["foo" => ["bar" => ["baz" => "Hello world."]]],
            ],
            "deep" => [
                "Hello world.",
                "foo.bar.baz",
                ["foo" => []],
                ["foo" => ["bar" => ["baz" => "Hello world."]]],
            ],
            "append" => [
                "Hello world.",
                "foo.bar.baz",
                ["foo" => ["bar" => ["A" => "one", "B" => "two", "C" => "three"]]],
                ["foo" => ["bar" => ["A" => "one", "B" => "two", "C" => "three", "baz" => "Hello world."]]],
            ],
        ];
        return $result;
    }

    /**
     * Test setting a value by path where an existing segment is not an array.
     */
    public function testSetByPathInvalidElement(): void {
        $array = ["foo" => ["bar" => "Hello world."]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unexpected type in path.");
        ArrayUtils::setByPath("Bingo.", "foo.bar.baz", $array);
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

    /**
     * Test `ArrayUtils::ArrayMergeRecursiveFancy()`.
     *
     * @param array $arr1
     * @param array $arr2
     * @param array $expected
     * @dataProvider provideArrayMergeRecursiveTests
     */
    public function testArrayMergeRecursiveFancy(array $arr1, array $arr2, array $expected): void {
        $tree = function (array &$arr) {
            $arr['child'] = $arr;
            unset($arr['child']['child']);
        };
        $tree($arr1);
        $tree($arr2);
        $tree($expected);

        $actual = ArrayUtils::arrayMergeRecursive($arr1, $arr2);

        ksort($actual);
        ksort($expected);
        $this->assertSame($expected, $actual);
    }

    public function provideArrayMergeRecursiveTests(): array {
        $r = [
            'overwrite' => [
                ['a' => 'a'], ['a' => 'b'], ['a' => 'b']
            ],
            'just 1' => [
                ['a' => 'a'], [], ['a' => 'a']
            ],
            'just 2' => [
                [], ['a' => 'a'], ['a' => 'a']
            ],
            'numeric' => [
                ['a' => ['a', 'b']], ['a' => ['a', 'c']], ['a' => ['a', 'b', 'c']]
            ],
            'just recurse 1' => [
                ['a' => ['b' => 'c']], [], ['a' => ['b' => 'c']]
            ],
            'just recurse 2' => [
                [], ['a' => ['b' => 'c']], ['a' => ['b' => 'c']]
            ],
            'empty numeric' => [
                ['a' => []], ['a' => ['a']], ['a' => ['a']]
            ],
            'numeric empty' => [
                ['a' => ['a']], ['a' => []], ['a' => ['a']]
            ],
            'mismatch' => [
                ['a' => ['a']], ['a' => 'a'], ['a' => 'a']
            ],
            'root numeric' => [
                ['a', 'b'], ['a', 'c'], ['a', 'b', 'c']
            ],
        ];
        return $r;
    }
}
