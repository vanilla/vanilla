<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\GettyImagesEmbed;
use Vanilla\EmbeddedContent\Factories\GettyImagesEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class GettyImagesEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var GettyImagesEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp(): void {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new GettyImagesEmbedFactory($this->httpClient);
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
            [
                "https://www.gettyimages.ca/detail/photo/sunset-view-of-parc-jean-drapeau-royalty-free-image/840894796",
                "https://www.gettyimages.com/detail/photo/sunset-view-of-parc-jean-drapeau-royalty-free-image/840894796",
                "https://gty.im/840894796",
            ],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://www.gettyimages.com/detail/photo/sunset-view-of-parc-jean-drapeau-royalty-free-image/840894796";
        $embedSignature = "hEvB-nIzdwAH4hElxYgbaupP4gPn42N1gNyunZfqD2E=";
        $foreignID = "lxyvCsYwR-pWsnGRRtcTFw";
        $photoID = "840894796";

        $oembedParams = http_build_query([
            "url" => "https://gty.im/{$photoID}",
        ]);
        $oembedUrl = GettyImagesEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "type" => "rich",
            "version" => "1.0",
            "height" => 360,
            "width" => 480,
            "html" => "<a id='lxyvCsYwR-pWsnGRRtcTFw' class='gie-single' href='http://www.gettyimages.com/detail/840894796' target='_blank' style='color:#a7a7a7;text-decoration:none;font-weight:normal !important;border:none;display:inline-block;'>Embed from Getty Images</a><script>window.gie=window.gie||function(c){(gie.q=gie.q||[]).push(c)};gie(function(){gie.widgets.load({id:'lxyvCsYwR-pWsnGRRtcTFw',sig:'hEvB-nIzdwAH4hElxYgbaupP4gPn42N1gNyunZfqD2E=',w:'480px',h:'360px',items:'840894796',caption: false ,tld:'com',is360: false })});</script><script src='//embed-cdn.gettyimages.com/widgets.js' charset='utf-8' async></script>",
            "title" => "sunset view of Parc Jean-Drapeau",
            "caption" => "Sunset aerial view of Jean-Drapeau Island besides Montreal city",
            "photographer" => "Zhou Jiang",
            "collection" => "Moment",
            "thumbnail_url" => "http://media.gettyimages.com/photos/sunset-view-of-parc-jeandrapeau-picture-id840894796?s=170x170",
            "thumbnail_height" => 127,
            "thumbnail_width" => 170,
            "terms_of_use_url" => "http://www.gettyimages.com/Corporate/Terms.aspx",
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
                "name" => $data["title"],
                "photoUrl" => $data["thumbnail_url"],
                "photoID" => $photoID,
                "foreignID" => $foreignID,
                "embedSignature" => $embedSignature,
                "url" => $url,
                "embedType" => GettyImagesEmbed::TYPE,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new GettyImagesEmbed($embedData);
        $this->assertInstanceOf(GettyImagesEmbed::class, $dataEmbed);
    }
}
