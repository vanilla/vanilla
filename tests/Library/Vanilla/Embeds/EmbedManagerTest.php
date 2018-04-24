<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Embeds;

use Exception;
use Garden\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use Vanilla\Embeds\EmbedManager;
use Vanilla\Embeds\LinkEmbed;
use Vanilla\Embeds\ImageEmbed;
use Vanilla\Embeds\YouTubeEmbed;
use Vanilla\Embeds\VimeoEmbed;
use VanillaTests\Fixtures\PageInfo;
use VanillaTests\Fixtures\NullCache;

class EmbedManagerTest extends TestCase {

    /**
     * Create a new EmbedManager instance.
     *
     * @return EmbedManager
     */
    private function createEmbedManager(): EmbedManager {
        $embedManager = new EmbedManager(new NullCache(), new ImageEmbed);
        $embedManager->setDefaultEmbed(new LinkEmbed(new PageInfo(new HttpRequest())))
            ->addEmbed(new YouTubeEmbed())
            ->addEmbed(new VimeoEmbed())
            ->addEmbed(new ImageEmbed(), EmbedManager::PRIORITY_LOW)
            ->setNetworkEnabled(false);
        return $embedManager;
    }

    /**
     * Provide parameters for verifying rendered data.
     *
     * @return array
     */
    public function provideRenderedData() {
        $data = [
            [
                [
                    "url" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "type" => "image",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height" => 630,
                    "width" => 1200,
                    "attributes" => []
                ],
                '<div class="embed-image embed embedImage">
    <img class="embedImage-img" src="https://vanillaforums.com/images/metaIcons/vanillaForums.png">
</div>'
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<a class="embed-link embed embedLink" href="https://vanillaforums.com" target="_blank" rel="noopener noreferrer">
    <article class="embedLink-body">
        <div class="embedLink-image" aria-hidden="true" style="background-image: url(https://vanillaforums.com/images/metaIcons/vanillaForums.png);"></div>
        <div class="embedLink-main">
            <div class="embedLink-header">
                <h3 class="embedLink-title">Online Community Software and Customer Forum Software by Vanilla Forums</h3>
                <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
            </div>
        </div>
    </article>
</a>'
            ],
            [
                [
                    "url" => "https://www.youtube.com/watch?v=9bZkp7q19f0",
                    "type" => "youtube",
                    "name" => "YouTube",
                    "body" => null,
                    "photoUrl" => "https://i.ytimg.com/vi/9bZkp7q19f0/hqdefault.jpg",
                    "height" => 270,
                    "width" => 480,
                    "attributes" => [
                        "thumbnail_width" => 480,
                        "thumbnail_height" => 360,
                        "videoID" => "9bZkp7q19f0"
                    ]
                ],
                '<span class="VideoWrap">
    <span class="Video YouTube" data-youtube="youtube-9bZkp7q19f0?autoplay=1">
        <span class="VideoPreview">
            <a href="https://www.youtube.com/watch?v=9bZkp7q19f0">
                <img src="https://img.youtube.com/vi/9bZkp7q19f0/0.jpg" width="480" height="270" border="0" />
            </a>
        </span>
        <span class="VideoPlayer"></span>
    </span>
</span>'
            ],
            [
                [
                    "url" => "https://vimeo.com/264197456",
                    "type" => "vimeo",
                    "name" => "Vimeo",
                    "body" => null,
                    "photoUrl" => "https://i.vimeocdn.com/video/694532899_640.jpg",
                    "height" => 272,
                    "width" => 640,
                    "attributes" => [
                        "thumbnail_width" => 640,
                        "thumbnail_height" => 272,
                        "videoID" => "264197456"
                    ]
                ],
                '<iframe src="https://player.vimeo.com/video/264197456" width="640" height="272" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'
            ]
        ];
        return $data;
    }

    /**
     * Verify rendered data results.
     *
     * @param array $data
     * @param string $expected
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     * @dataProvider provideRenderedData
     */
    public function testRenderData(array $data, string $expected) {
        $embedManager = $this->createEmbedManager();
        $actual = $embedManager->renderData($data);
        $this->assertEquals($expected, $actual);
    }
}
