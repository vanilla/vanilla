<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for forceIPv4().
 */

class ForceIPv4Test extends TestCase {

    /**
     * Tests {@link forceIPv4()} against various scenarios.
     *
     * @param string $testIP The IP address to force.
     * @param string $expected The expected result.
     * @dataProvider provideTestForceIPv4Arrays
     */
    public function testForceIPv4($testIP, $expected) {
        $actual = forceIPv4($testIP);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link forceIPv4()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestForceIPv4Arrays() {
        $r = [
            'colonColonOne' => [
                '::1',
                '127.0.0.1',
            ],
            'stringWithColon' => [
                '2001:db8:aaaa:1:0:0:0:200',
                '0.0.0.2',
            ],
            'stringWithPeriod' => [
                '198.3.4.5',
                '198.3.4.5',
            ],
        ];

        return $r;
    }
}
