<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for ipDecode() and ipEncode().
 */

class IpEncodeDecodeNonRecursiveTest extends TestCase {

    /**
     * Test {@link ipDecode()} against already valid IP address and a garbage string.
     *
     * @param string $nonPackedIP A string representing a packed IP address.
     * @param string|null $expected The expected result.
     * @dataProvider provideTestIpDecodeArrays
     */
    public function testIpDecode($nonPackedIP, $expected) {
        $actual = ipDecode($nonPackedIP);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test {@link ipEncode()} and {@link ipDecode} for compatibility.
     *
     * @param string $ipAddress
     * @dataProvider provideIPAdresses
     */
    public function testIpEncodeDecode(string $ipAddress): void {
        $ipE = ipEncode($ipAddress);
        $ipD = ipDecode($ipE);
        $this->assertSame($ipAddress, $ipD);
    }

    /**
     * Provide test data for {@link ipDecode()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestIpDecodeArrays() {
        $r = [
            'IPv4' => [
                '92.120.48.49',
                '92.120.48.49',
            ],
            'IPv6' => [
                '5c78:6330:5c78:6138:5c78:3030:5c78:3031',
                '5c78:6330:5c78:6138:5c78:3030:5c78:3031',
            ],
            'garbage' => [
                'aiohadp089aw4tahija;slkjgpi9ia',
                null,
            ]
        ];

        return $r;
    }

    /**
     * Provide IP Address test data.
     *
     * @return array Returns an array of test data.
     */
    public function provideIPAdresses() {
        $r = [
            'ipV4' => [
                '92.120.48.49',
            ],
            'ipV6' => [
                '5c78:6330:5c78:6138:5c78:3030:5c78:3031',
            ],
        ];

        return $r;
    }
}
