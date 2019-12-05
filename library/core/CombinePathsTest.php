<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;


use PHPUnit\Framework\TestCase;

/**
 * Tests for combinePaths().
 */
class CombinePathsTest extends TestCase {

    /**
     * Test {@link combinePaths()} against several scenarios.
     *
     * @param array|string $testPaths The array of paths to concatenate.
     * @param string $testDelimiter The delimiter to use when concatenating.
     * @param string $expected The expected result.
     * @dataProvider provideCombinePathsArrays
     */

    public function testCombinePaths($testPaths, string $testDelimiter, string $expected) {
        $actual = combinePaths($testPaths, $testDelimiter);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link combinePaths()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideCombinePathsArrays() {
        $r = [
            'combineTwoPaths' => [
                ['path1/path2', 'path3/path4'],
                '/',
                'path1/path2/path3/path4',
            ],
            'combineWithHttp' => [
                ['http:', 'path1/path2', 'path3'],
                '/',
                'http://path1/path2/path3',
            ],
            'combineWithHttps' => [
                ['https:', 'path1/path2', 'path3'],
                '/',
                'https://path1/path2/path3',
            ],
            'combineWithString' => [
                'path1/path2',
                '/',
                'path1/path2',
            ]
        ];

        return $r;
    }


}
