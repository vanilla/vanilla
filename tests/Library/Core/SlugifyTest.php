<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for slugify().
 */

class SlugifyTest extends TestCase {

    /**
     * Tests {@link slugify()} against several scenarios.
     *
     * @param string $testText The text to convert.
     * @param string $expected The expected result.
     * @dataProvider provideTestSlugifyArrays
     */
    public function testSlugify($testText, $expected) {
        $actual = slugify($testText);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link slugify()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSlugifyArrays() {
        $r = [
            'emptyString' => [
                '',
                'n-a',
            ],
            'upperCaseString' => [
                'Text',
                'text',
            ],
            'stringWithMultipleConsecutiveSpaces' => [
                'This     text   has     many     spaces',
                'this-text-has-many-spaces',
            ],
            'testRemovingDuplicateHyphens' => [
                'this text......has many periods',
                'this-text-has-many-periods',
            ],
            'stringWithEszett' => [
                'this string has an Eßzett',
                'this-string-has-an-esszett',
            ],
        ];

        return $r;
    }
}
