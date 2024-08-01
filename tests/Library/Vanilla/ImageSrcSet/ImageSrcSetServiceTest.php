<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\ImageSrcSet;

use Vanilla\ImageSrcSet\ImageSrcSetService;
use VanillaTests\Fixtures\MockImageSrcSetProvider;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the ImageSrcSetService class.
 */
class ImageSrcSetServiceTest extends MinimalContainerTestCase
{
    /**
     * Test srcset creation using a MockImageSrcSetProvider.
     */
    public function testCreatingResizedSrcSet()
    {
        $mockImageSrcSetProvider = new MockImageSrcSetProvider();

        $imageSrcSetService = new ImageSrcSetService();
        $imageSrcSetService->setImageResizeProvider($mockImageSrcSetProvider);

        $srcImageUrl = "https://test.net/files/1539622619/bacon.jpg";
        $desiredOutput = [
            10 => "https://loremflickr.com/g/10/600/bacon",
            300 => "https://loremflickr.com/g/300/600/bacon",
            800 => "https://loremflickr.com/g/800/600/bacon",
            1200 => "https://loremflickr.com/g/1200/600/bacon",
            1600 => "https://loremflickr.com/g/1600/600/bacon",
        ];

        $imageSrcSet = $imageSrcSetService->getResizedSrcSet($srcImageUrl);

        $this->assertEquals($desiredOutput, $imageSrcSet->jsonSerialize());
    }

    /**
     * Test srcset creation when MockImageSrcSetProvider::getResizedImageUrl() returns null.
     */
    public function testResizedSrcSetWithNullUrl()
    {
        $mockImageSrcSetProvider = $this->createMock(MockImageSrcSetProvider::class);
        $mockImageSrcSetProvider
            ->expects($this->any())
            ->method("getResizedImageUrl")
            ->willReturn(null);

        $imageSrcSetService = new ImageSrcSetService();
        $imageSrcSetService->setImageResizeProvider($mockImageSrcSetProvider);

        $srcImageUrl = "https://test.net/files/1539622619/bacon.jpg";
        $desiredOutput = null;

        $imageSrcSet = $imageSrcSetService->getResizedSrcSet($srcImageUrl);
        $this->assertSame($desiredOutput, $imageSrcSet->jsonSerialize());
    }
}
