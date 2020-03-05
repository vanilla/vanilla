<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for domGetImages().
 */

class DomGetImagesTest extends TestCase {

    /**
     * Tests {@link domGetImages()} against several scenarios.
     *
     * @param pQuery $testDom The DOM to search.
     * @param string $testUrl The URL of the document to add to relative URLs.
     * @param int $testMaxImages The maximum number of images to return.
     * @param array $expected The expected result.
     * @dataProvider provideTestDomGetImagesArrays
     */
    public function testDomGetImages($testDom, $testUrl, $testMaxImages, $expected) {
        $testDom = \pQuery::parseStr($testDom);
        $actual = domGetImages($testDom, $testUrl, $testMaxImages);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link domGetImages()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestDomGetImagesArrays() {
        $r = [
            'testOneImage' => [
                '<div class="story-teaser__img-wrap">
                    <img src="/image1" height="400" width="400"/>     
                 </div>',
                'https://www.example.com/',
                1,
                [0 => "https://www.example.com/image1"],
            ],
            'testDoubleClickCondition' => [
                '<div class="story-teaser__img-wrap">
                    <img src="https://www.doubleclick.com"/>     
                 </div>',
                'https://www.example.com',
                1,
                [],
            ],
            'testImageTooSmall' => [
                '<div class="story-teaser__img-wrap">
                    <img src="/image1" height="90" width="90"/>
                 </div>',
                'https://www.example.com/',
                1,
                [],
            ],
            'testBannerImage' => [
                '<div class="story-teaser__img-wrap">
                    <img src="/image1" height="90" width="400"/>
                </div>',
                'https://www.example.com/',
                1,
                [],
            ],
        ];

        return $r;
    }
}
