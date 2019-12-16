<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for formatString().
 */

class FormatStringTest extends TestCase {

    /**
     * Test {@link formatString()} against several scenarios.
     *
     * @param string $testString The string to format with fields from its args enclosed in an array.
     * @param array $testArgs The array of arguments.
     * @param string $expected The expected result.
     * @dataProvider provideTestFormatStringArrays
     */
    public function testFormatString($testString, $testArgs, $expected) {
        $actual = formatString($testString, $testArgs);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testFormatString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestFormatStringArrays() {
        $r = [
            'exampleFromFunctionDoc' => [
                "Hello {Name}, It's {Now, time}.",
                ['Name' => "Frank", 'Now' => '1999-12-31 23:59'],
                "Hello Frank, It's 12:59PM.",
            ],
        ];

        return $r;
    }
}
