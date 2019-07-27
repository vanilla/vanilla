<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\ImgurEmbed;
use Vanilla\EmbeddedContent\Factories\ImgurEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class ImgurEmbedFactoryTestMinimal extends MinimalContainerTestCase {

    /** @var ImgurEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new ImgurEmbedFactory($this->httpClient);
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
            [ "https://imgur.com/a/Pt2cHff" ],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://imgur.com/a/Pt2cHff";
        $imgurID = "a/Pt2cHff";

        $oembedParams = http_build_query([ "url" => $url ]);
        $oembedUrl = ImgurEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "version" => "1.0",
            "type" => "rich",
            "provider_name" => "Imgur",
            "provider_url" => "https://imgur.com",
            "width" => 540,
            "height" => 500,
            "html" => "<blockquote class=\"imgur-embed-pub\" lang=\"en\" data-id=\"a/Pt2cHff\"><a href=\"https://imgur.com/a/Pt2cHff\">Very scary birb </a></blockquote><script async src=\"//s.imgur.com/min/embed.js\" charset=\"utf-8\"></script>",
            "author_name" => "monalistic",
            "author_url" => "https://imgur.com/user/monalistic",
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
                "imgurID" => $imgurID,
                "url" => $url,
                "embedType" => ImgurEmbed::TYPE,
                "name" => "",
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new ImgurEmbed($embedData);
        $this->assertInstanceOf(ImgurEmbed::class, $dataEmbed);
    }
}
