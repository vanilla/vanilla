<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for write_ini_string().
 */

class WriteIniStringTest extends TestCase {

    /**
     * Test {@link write_ini_string()} against several scenarios.
     *
     * @param array $testData The data to format.
     * @param string $expected The expected result.
     * @dataProvider provideTestWriteIniStringArrays
     */
    public function testWriteIniString($testData, $expected) {
        $actual = write_ini_string($testData);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link write_ini_string()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestWriteIniStringArrays() {
        $r = [
            'emptyArray' => [
                [],
                ""
            ],
            'stringValues' => [
                ['key1' => 'val1', 'key2' => 'val2'],
                "key1 = \"val1\"\nkey2 = \"val2\"",
            ],
            'intValues' => [
                ['key1' => 1, 'key2' => 2],
                "key1 = 1\nkey2 = 2",
            ],
            'arrayValues' => [
                ['key1' => [1, 2, 3], 'key2' => ['one', 'two', 'three']],
                "[key1]\n0 = 1\n1 = 2\n2 = 3\n\n[key2]\n0 = \"one\"\n1 = \"two\"\n2 = \"three\"\n",
            ],
        ];

        return $r;
    }
}
