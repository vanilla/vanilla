<?php
/**
* @author Richard Flynn <richard.flynn@vanillaforums.com>
* @copyright 2009-2019 Vanilla Forums Inc.
* @license GPL-2.0-only
*/

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for debug().
 */

class DebugTest extends TestCase {

    /**
     * Test {@link debug()} against several scenarios.
     *
     * @param bool|null $testValue The new debug value or null to return the current value.
     * @param bool $expected The expected result.
     * @dataProvider provideDebugArrays
     */
    public function testDebug($testValue, $expected) {
        $actual = debug($testValue);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for (@link debug()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideDebugArrays() {
        $r = [
            'nullCase' => [
                null,
                false,
            ],
            'trueCase' => [
                true,
                true,
            ],
            'false case' => [
                false,
                false,
            ],
            'badInput' => [
                'badInput',
                'badInput',
            ],
        ];

        return $r;
    }
}
