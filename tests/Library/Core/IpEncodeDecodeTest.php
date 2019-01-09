<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use PHPUnit\Framework\TestCase;

/**
 * Test some of the global functions that operate (or mostly operate) on arrays.
 */
class IpEncodeDecodeTest extends TestCase {
    /**
     * Test encoding/decoding IPs in array.
     *
     * @param array $data
     * @param array $expected
     *
     * @dataProvider provideArrays
     */
    public function testIpEncodeRecursiveArray(array $data, array $expected) {
        $this->assertEquals($expected, ipEncodeRecursive($data));
    }

    /**
     * Provide arrays to test against ipEncodeRecursive()
     *
     * @return array
     */
    public function provideArrays() {
        return [
            'array-w-junk' => [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            'array-w-ips' => [
                ['12.22.23.34', '12.12.12.12'],
                ['12.22.23.34', '12.12.12.12'],
            ],
            'associative-array' => [
                ['SomeIPAddress' => '12.22.23.34', 'SomethingElse' => '255.255.255.255'],
                ['SomeIPAddress' => ipencode('12.22.23.34'), 'SomethingElse' => '255.255.255.255'],
            ],
            'nested-array' => [
                [['12.13.14.15'], ['13.13.13.13']],
                [['12.13.14.15'], ['13.13.13.13']]
            ],
            'nested-associative-array' => [
                ['SomeIPAddress' => ['SomeIPAddress' => '12.13.14.15'], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']],
                ['SomeIPAddress' => ['SomeIPAddress' => ipencode('12.13.14.15')], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']]
            ]
        ];
    }
    /**
     * Test decoding IPs in array.
     *
     * @param array $data
     * @param array $expected
     *
     * @dataProvider provideArraysDecode
     */
    public function testIpDecodeRecursiveArray(array $data, array $expected) {
        $this->assertEquals($expected, ipDecodeRecursive($data));
    }

    /**
     * Provide arrays to test against ipDecodeRecursive()
     *
     * @return array
     */
    public function provideArraysDecode() {
        return [
            'array-w-junk' => [
                ['a', 'b', 'c'],
                ['a', 'b', 'c'],
            ],
            'array-w-ips' => [
                ['12.22.23.34', '12.12.12.12'],
                ['12.22.23.34', '12.12.12.12'],
            ],
            'associative-array' => [
                ['SomeIPAddress' => ipencode('12.22.23.34'), 'SomethingElse' => '255.255.255.255'],
                ['SomeIPAddress' => '12.22.23.34', 'SomethingElse' => '255.255.255.255'],
            ],
            'nested-array' => [
                [['12.13.14.15'], ['13.13.13.13']],
                [['12.13.14.15'], ['13.13.13.13']]
            ],
            'nested-associative-array' => [
                ['SomeIPAddress' => ['SomeIPAddress' => ipencode('12.13.14.15')], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']],
                ['SomeIPAddress' => ['SomeIPAddress' => '12.13.14.15'], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']]
            ]
        ];
    }

    /**
     * Test encoding IPs in an object.
     *
     * @param object $data
     * @param object $expected
     *
     * @dataProvider provideObjects
     */
    public function testIpEncodeRecursiveObject($data, $expected) {
        $this->assertEquals($expected, ipEncodeRecursive($data));
    }

    /**
     * Provide objects to test against ipEncodeRecursive()
     *
     * @return array
     */
    public function provideObjects() {
        return [
           'junk' => [
               (object) ['a', 'b', 'c'],
               (object) ['a', 'b', 'c'],
           ],
           'ips' => [
               (object) ['12.22.23.34', '12.12.12.12'],
               (object) ['12.22.23.34', '12.12.12.12'],
           ],
           'associative' => [
               (object) ['SomeIPAddress' => '12.22.23.34', 'SomethingElse' => '255.255.255.255'],
               (object) ['SomeIPAddress' => ipencode('12.22.23.34'), 'SomethingElse' => '255.255.255.255'],
           ],
           'nested' => [
               (object) [['12.13.14.15'], ['13.13.13.13']],
               (object) [['12.13.14.15'], ['13.13.13.13']]
           ],
           'nested-associative' => [
               (object) ['SomeIPAddress' => ['SomeIPAddress' => '12.13.14.15'], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']],
               (object) ['SomeIPAddress' => ['SomeIPAddress' => ipencode('12.13.14.15')], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']]
           ]
        ];
    }
    /**
     * Test decoding IPs in an object.
     *
     * @param object $data
     * @param object $expected
     *
     * @dataProvider provideObjectsDecode
     */
    public function testIpDecodeRecursiveObject($data, $expected) {
        $this->assertEquals($expected, ipDecodeRecursive($data));
    }

    /**
     * Provide objects to test against ipDecodeRecursive()
     *
     * @return array
     */
    public function provideObjectsDecode() {
        return [
            'junk' => [
                (object) ['a', 'b', 'c'],
                (object) ['a', 'b', 'c'],
            ],
            'ips' => [
                (object) ['12.22.23.34', '12.12.12.12'],
                (object) ['12.22.23.34', '12.12.12.12'],
            ],
            'associative' => [
                (object) ['SomeIPAddress' => ipencode('12.22.23.34'), 'SomethingElse' => '255.255.255.255'],
                (object) ['SomeIPAddress' => '12.22.23.34', 'SomethingElse' => '255.255.255.255'],
            ],
            'nested' => [
                (object) [['12.13.14.15'], ['13.13.13.13']],
                (object) [['12.13.14.15'], ['13.13.13.13']]
            ],
            'nested-associative' => [
                (object) ['SomeIPAddress' => ['SomeIPAddress' => ipencode('12.13.14.15')], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']],
                (object) ['SomeIPAddress' => ['SomeIPAddress' => '12.13.14.15'], 'SomethingElse' => ['SomethingElse' => '255.255.255.255']]
            ]
        ];
    }
}
