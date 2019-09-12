<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\CodePenEmbed;
use Vanilla\EmbeddedContent\Factories\CodePenEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the giphy embed and factory.
 */
class CodePenEmbedFactoryTest extends MinimalContainerTestCase {

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
        $cpId = 'YoKYVv';

        $this->httpClient->addMockResponse(
            $endpoint,
            new HttpResponse(
                200,
                'Content-Type: application/json',
                json_encode([
                    'width' => $width,
                    'title' => $name,
                    'height' => $height,
                    'html' => "<iframe id='cp_embed_$cpId' src='$frameSrc'></iframe>",
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
                'embedType' => CodePenEmbed::TYPE,
                'codePenID' => $cpId,
                'author' => 'hiroshi_m',
            ],
            $embedData,
            'Data can be fetched over the network to create the embed from a URL.'
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new CodePenEmbed($embedData);
        $this->assertInstanceOf(CodePenEmbed::class, $dataEmbed);
    }
}
