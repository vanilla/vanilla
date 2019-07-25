<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\VimeoEmbed;
use Vanilla\EmbeddedContent\Factories\VimeoEmbedFactory;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class VimeoEmbedFactoryTest extends ContainerTestCase {

    /** @var VimeoEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new VimeoEmbedFactory($this->httpClient);
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
            [ "https://vimeo.com/207028770" ],
            [ "https://vimeo.com/344997253/ab1b6f2867" ], // Alternate video syntax
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://vimeo.com/207028770";
        $frameSrc = "https://player.vimeo.com/video/207028770?autoplay=1";

        $oembedParams = http_build_query([ "url" => $url ]);
        $oembedUrl = VimeoEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "type" => "video",
            "version" => "1.0",
            "provider_name" => "Vimeo",
            "provider_url" => "https://vimeo.com/",
            "title" => "Glycol",
            "author_name" => "Diego Diapolo",
            "author_url" => "https://vimeo.com/diegodiapolo",
            "is_plus" => "0",
            "account_type" => "basic",
            "html" => "<iframe src=\"https://player.vimeo.com/video/207028770?app_id=122963\" width=\"640\" height=\"300\" frameborder=\"0\" title=\"Glycol\" allow=\"autoplay; fullscreen\" allowfullscreen></iframe>",
            "width" => 640,
            "height" => 300,
            "duration" => 70,
            "description" => "Glycol decomposes in contact with the air in about ten days, in water or soil in just a couple of weeks. Plastic spaces and living things are made of it, habitating in an artificial world created by humans, a world of perpetual motion and random algorithms. It is aesthetic, mathematical and physical. Glycol is an experiment I did to communicate with you.\n\nAll by Diego Diapolo\n\nmore at \nwww.diegodiapolo.com\nwww.instagram.com/diegodiapolo",
            "thumbnail_url" => "https://i.vimeocdn.com/video/740788474_640.jpg",
            "thumbnail_width" => 640,
            "thumbnail_height" => 300,
            "thumbnail_url_with_play_button" => "https://i.vimeocdn.com/filter/overlay?src0=https%3A%2F%2Fi.vimeocdn.com%2Fvideo%2F740788474_640.jpg&src1=http%3A%2F%2Ff.vimeocdn.com%2Fp%2Fimages%2Fcrawler_play.png",
            "upload_date" => "2017-03-05 17:33:52",
            "video_id" => 207028770,
            "uri" => "/videos/207028770",
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
                "videoID" => $data["video_id"],
                "url" => $url,
                "embedType" => VimeoEmbed::TYPE,
                "name" => $data["title"],
                "frameSrc" => $frameSrc,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new VimeoEmbed($embedData);
        $this->assertInstanceOf(VimeoEmbed::class, $dataEmbed);
    }
}
