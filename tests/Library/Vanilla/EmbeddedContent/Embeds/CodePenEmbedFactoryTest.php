<?php


/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\CodePenEmbed;
use Vanilla\EmbeddedContent\Embeds\CodePenEmbedFactory;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the giphy embed and factory.
 */
class CodePenEmbedFactoryTest extends ContainerTestCase {

    /** @var CodePenEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new CodePenEmbedFactory($this->httpClient);
    }


    /**
     * Test that all giphy domain types are supported.
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
            ['https://codepen.io/hiroshi_m/pen/YoKYVv'], // Only 1 image format.
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $urlToCheck = 'https://codepen.io/hiroshi_m/pen/YoKYVv';
        $endpoint = CodePenEmbedFactory::OEMBED_URL_BASE . '?url=' . urlencode($urlToCheck) . '&format=json';

        $name = 'Hello title';
        $width = 500;
        $height = 400;
        $frameSrc = 'https://codepen.io/hiroshi_m/embed/preview/YoKYVv?height=300';
        $cpId = 'cp_embed_YoKYVv';

        $this->httpClient->addMockResponse(
            $endpoint,
            new HttpResponse(
                200,
                'Content-Type: application/json',
                json_encode([
                    'width' => $width,
                    'title' => $name,
                    'height' => $height,
                    'html' => "<iframe id='$cpId' src='$frameSrc'></iframe>",
                ])
            )
        );

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($urlToCheck);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                'width' => $width,
                'name' => $name,
                'height' => $height,
                'url' => $urlToCheck, // The original URL.
                'type' => CodePenEmbed::TYPE,
                'codepenID' => $cpId,
                'frameSrc' => $frameSrc,
            ],
            $embedData,
            'Data can be fetched over the network to create the embed from a URL.'
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = $this->factory->createEmbedFromData($embedData);
        $this->assertInstanceOf(CodePenEmbed::class, $dataEmbed);
    }

    /**
     * Ensure we can create giphy embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://codepen.io/hiroshi_m/pen/YoKYVv",
    "type": "codepen",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": 300,
    "width": null,
    "attributes": {
        "id": "cp_embed_YoKYVv",
        "embedUrl": "https://codepen.io/hiroshi_m/embed/preview/YoKYVv?theme-id=0",
        "style": { "width": " 100%", "overflow": "hidden" }
    }
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = $this->factory->createEmbedFromData($oldData);
        $this->assertInstanceOf(CodePenEmbed::class, $dataEmbed);
    }
}
