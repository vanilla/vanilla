<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for prefixString().
 */

class PrefixStringTest extends TestCase {

    /**
     * Tests {@link prefixString()} against several scenarios.
     *
     * @param string $testPrefix The prefix to use.
     * @param string $testString The string to be prefixed.
     * @param string $expected The expected result.
     * @dataProvider provideTestPrefixStringArrays
     */
    public function testPrefixString($testPrefix, $testString, $expected) {
        $actual = prefixString($testPrefix, $testString);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link prefixString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestPrefixStringArrays() {
        $r = [
            'emptyString' => [
                'prefix',
                '',
                'prefix',
            ],
            'emptyPrefix' => [
                '',
                'string',
                'string',
            ],
            'normalCase' => [
                'prefix',
                'String',
                'prefixString',
            ],
            'bothEmpty' => [
                '',
                '',
                '',
            ],
            'prefixAlreadyExists' => [
                'prefix',
                'prefixString',
                'prefixString',
            ],
        ];

        return $r;
    }
}
