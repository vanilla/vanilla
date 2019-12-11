<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for absoluteSource()
 */

class AbsoluteSourceTest extends TestCase {

    /**
     * Tests {@link absoluteSource()} against several scenarios.
     *
     * @param string $testSrcPath The source path to make absolute (if not absolute already).
     * @param string $testUrl The full url to the page containing the src reference.
     * @param string $expected The expected result.
     * @dataProvider provideTestAbsoluteSourceArrays
     */
    public function testAbsoluteSource($testSrcPath, $testUrl, $expected) {
        $actual = absoluteSource($testSrcPath, $testUrl);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link absoluteSource()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestAbsoluteSourceArrays() {
        $r = [
            'pathAlreadyAbsolute' => [
                "https://compote.slate.com/images/51db07dd-254c-4474-840e-118a55abca0c.
                jpeg?width=780&amp;height=520&amp;rect=1560x1040&amp;offset=0x0",
                "https://slate.com/news-and-politics/2019/12/katie-hill-revenge-porn-
                journalism-ethics-red-state.html",
                "https://compote.slate.com/images/51db07dd-254c-4474-840e-118a55abca0c.
                jpeg?width=780&amp;height=520&amp;rect=1560x1040&amp;offset=0x0",
            ],
            'pathRelative' => [
                '/images/picture',
                'https://my-domain.com',
                'https://my-domain.com/images/picture',
            ],
            'noUrl' => [
                'images/picture',
                '',
                '',
            ],
            'badSrcPath' => [
                'http:///example.com',
                'https://my-domain.com',
                '',
            ],
        ];

        return $r;
    }
}
