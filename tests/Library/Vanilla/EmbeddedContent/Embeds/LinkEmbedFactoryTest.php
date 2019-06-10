<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbedFactory;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\Fixtures\MockPageScraper;

/**
 * Tests for the giphy embed and factory.
 */
class LinkEmbedFactoryTest extends ContainerTestCase {

    /** @var LinkEmbedFactory */
    private $factory;

    /** @var MockPageScraper */
    private $pageScraper;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->pageScraper = new MockPageScraper();
        $this->factory = new LinkEmbedFactory($this->pageScraper);
    }


    /**
     * Test that all giphy domain types are supported.
     *
     * @param string $urlToTest
     * @dataProvider unsupportedDomains
     */
    public function testSupportedDomains(string $urlToTest) {
        $this->assertFalse(
            $this->factory->canHandleUrl($urlToTest),
            "LinkEmbedFactory should not match any URLs. It should exclusively be a fallback."
        );
    }

    /**
     * @return array
     */
    public function unsupportedDomains(): array {
        return [
            ['https://tasdfasdf.com/asdfasd4-23e1//asdf31/1324'],
            ['http://asd.com'], // Empty paths.
            ['https://testasd.com/']
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $urlToCheck = 'https://test.com';

        $name = "Hello Title";
        $description = "Hello description";
        $images = ["https://test.com/image.png", "https://other.com/pic.jpg"];
        $this->pageScraper->addMockResponse($urlToCheck, [
            'Title' => $name,
            'Description' => $description,
            'Images' => $images,
            'Url' => $urlToCheck,
        ]);

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($urlToCheck);
        $this->assertInstanceOf(LinkEmbed::class, $embed);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                'name' => $name,
                'url' => $urlToCheck, // The original URL.
                'type' => LinkEmbed::TYPE,
                'body' => $description,
                'photoUrl' => $images[0],
            ],
            $embedData,
            'Data cna be fetched over the network to create the embed from a URL.'
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = $this->factory->createEmbedFromData($embedData);
        $this->assertInstanceOf(LinkEmbed::class, $dataEmbed);
    }

    /**
     * Ensure we can create giphy embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://vanillaforums.com/en/",
    "type": "link",
    "name": "Online Community Software and Customer Forum Software by Vanilla Forums",
    "body": "Engage your customers with a vibrant and modern online customer community forum.",
    "photoUrl": "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
    "height": null,
    "width": null,
    "attributes": []
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = $this->factory->createEmbedFromData($oldData);
        $this->assertInstanceOf(LinkEmbed::class, $dataEmbed);
    }
}
