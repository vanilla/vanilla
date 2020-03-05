<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for attribute().
 */

class AttributeTest extends TestCase {

    /**
     * Tests {@link attribute()} against several scenarios.
     *
     * @param string|array $testName The attribute array or the name of the attribute.
     * @param mixed $testValueOrExclude The value of the attribute or a prefix of attribute names to exclude.
     * @param string $expected The expected result.
     * @dataProvider provideTestAttributeArrays
     */
    public function testAttribute($testName, $testValueOrExclude, $expected) {
        $actual = attribute($testName, $testValueOrExclude);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testAttribute()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestAttributeArrays() {
        $r = [
            'testNameIsString' => [
              "width",
              '500',
              ' width="500"',
            ],
            'emptyValue' => [
                "width",
                false,
                '',
            ],
            'valueIsArray' => [
                ['data-something' => ['x' => '1', 'y' => '2']],
                [],
                ' data-something="{&quot;x&quot;:&quot;1&quot;,&quot;y&quot;:&quot;2&quot;}"'
            ]
        ];

        return $r;
    }
}
