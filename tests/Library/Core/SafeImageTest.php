<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\APIv2\Authenticate\PasswordAuthenticatorTest;

/**
 * Tests for safeImage().
 */

class SafeImageTest extends TestCase {

    /**
     * Tests {@link safeImage()} against various scenarios.
     *
     * @param string|bool $expected The expected result.
     * @param string $testImageUrl Url of the image to examine.
     * @param int $testMinHeight (in pixels) of image. 0 means any height.
     * @param int $testMinWidth (in pixels) of image. 0 means any width.
     * @dataProvider provideTestSafeImageArrays
     */
    public function testSafeImage($expected, string $testImageUrl, int $testMinHeight = 0, int $testMinWidth = 0) {
        $actual = safeImage($testImageUrl, $testMinHeight, $testMinWidth);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testSafeImage()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSafeImageArrays() {
        $r = [
            'basicTest' => [
                PATH_ROOT.'/tests/fixtures/safe-image/super-cat.jpg',
                PATH_ROOT.'/tests/fixtures/safe-image/super-cat.jpg',
            ],
            'noImage' => [
                false,
                PATH_ROOT.'/tests/fixtures/safe-image/empty-folder',
            ],
            'underMinHeight' => [
                false,
                PATH_ROOT.'/tests/fixtures/safe-image/super-cat.jpg',
                1,
            ],
            'underMinWidth' => [
                false,
                PATH_ROOT.'/tests/fixtures/safe-image/super-cat.jpg',
                0,
                1,
            ],
            'meetsMinHeight' => [
                PATH_ROOT.'/tests/'
            ]
        ];

        return $r;
    }
}
