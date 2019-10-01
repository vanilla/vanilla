<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\WistiaEmbed;
use Vanilla\EmbeddedContent\Factories\WistiaEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class WistiaEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var WistiaEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new WistiaEmbedFactory($this->httpClient);
    }

    /**
     * Test that all expected domains are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedDomainsProvider
     */
    public function testSupportedDomains(string $urlToTest) {
        $this->assertTrue($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * @return array
     */
    public function supportedDomainsProvider(): array {
        return [
            [ "https://dave.wistia.com/medias/0k5h1g1chs" ],
            [ "https://dave.wi.st/medias/0k5h1g1chs" ],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://dave.wistia.com/medias/0k5h1g1chs";
        $videoID = "0k5h1g1chs";
        $frameSrc = "https://fast.wistia.net/embed/iframe/0k5h1g1chs?autoPlay=1";

        $oembedParams = http_build_query([ "url" => $url ]);
        $oembedUrl = WistiaEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "version" => "1.0",
            "type" => "video",
            "html" => "<iframe src=\"https://fast.wistia.net/embed/iframe/0k5h1g1chs\" title=\"Lenny Delivers a Video - oEmbed glitch\" allowtransparency=\"true\" frameborder=\"0\" scrolling=\"no\" class=\"wistia_embed\" name=\"wistia_embed\" allowfullscreen mozallowfullscreen webkitallowfullscreen oallowfullscreen msallowfullscreen width=\"960\" height=\"540\"></iframe>\n<script src=\"https://fast.wistia.net/assets/external/E-v1.js\" async></script>",
            "width" => 960,
            "height" => 540,
            "provider_name" => "Wistia, Inc.",
            "provider_url" => "https://wistia.com",
            "title" => "Lenny Delivers a Video - oEmbed glitch",
            "thumbnail_url" => "https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540",
            "thumbnail_width" => 960,
            "thumbnail_height" => 540,
            "player_color" => "54bbff",
            "duration" => 40.264,
        ];
        // phpcs:enable Generic.Files.LineLength

        $this->httpClient->addMockResponse(
            $oembedUrl,
            new HttpResponse(
                200,
                "Content-Type: application/json",
                json_encode($data)
            )
        );

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($url);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                "height" => $data["height"],
                "width" => $data["width"],
                "photoUrl" => $data["thumbnail_url"],
                "videoID" => $videoID,
                "url" => $url,
                "embedType" => WistiaEmbed::TYPE,
                "name" => $data["title"],
                "frameSrc" => $frameSrc,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new WistiaEmbed($embedData);
        $this->assertInstanceOf(WistiaEmbed::class, $dataEmbed);
    }
}
