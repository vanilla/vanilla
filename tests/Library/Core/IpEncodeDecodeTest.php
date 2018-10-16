<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;

/**
 * Test some of the global functions that operate (or mostly operate) on arrays.
 */
class IpEncodeDecodeTest extends SharedBootstrapTestCase {

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
//            'nested-array' => DO ME
//            'nested-associative-array' => DO ME
        ];
    }

//    public function provideObjects() { DO ME
//
//    }

}
