<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for buildUrl().
 */

class BuildUrlTest extends TestCase {

    /**
     * Tests {@link buildUrl()} against several scenarios.
     *
     * @param array $testParts The parseUrl array to build.
     * @param string $expected The expected result.
     * @dataProvider provideTestBuildUrlArrays
     */
    public function testBuildUrlArrays($testParts, $expected) {
        $actual = buildUrl($testParts);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link buildUrl()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestBuildUrlArrays() {
        $r = [
            'allPartsAccountedFor' => [
                ['scheme' => 'http',
                    'user' => 'dick',
                    'pass' => 'secret',
                    'host' => 'dicks-house',
                    'port' => '1980',
                    'path' => 'dinner',
                    'query' => 'what-are-we-having?',
                    'fragment' => 'tacos-again',],
                'http://dick:secret@dicks-house:1980/dinner?what-are-we-having?#tacos-again',
            ],
            'noValues' => [
                ['scheme' => '',
                    'user' => '',
                    'pass' => '',
                    'host' => '',
                    'port' => '',
                    'path' => '',
                    'query' => '',
                    'fragment' => '',],
                '://',
            ],
        ];

        return $r;
    }
}
