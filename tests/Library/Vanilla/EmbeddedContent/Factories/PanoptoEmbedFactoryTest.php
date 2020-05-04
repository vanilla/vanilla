<?php
/**
 * @author Patrick Desjardins <patrick.d@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\Embeds\PanoptoEmbed;
use Vanilla\EmbeddedContent\Factories\PanoptoEmbedFactory;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the Panopto embed factory.
 */
class PanoptoEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var PanoptoEmbedFactory */
    private $factory;

    /**
     * Set the factory.
     */
    public function setUp(): void {
        parent::setUp();
        $this->factory = new PanoptoEmbedFactory();
    }

    /**
     * Test that all domain types are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testSupportedUrls(string $urlToTest) {
        $this->assertTrue($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * Return an array of supported urls.
     *
     * @return array
     */
    public function supportedUrlsProvider(): array {
        return [
            ['https://howtovideos.hosted.panopto.com/Panopto/Pages/Viewer.aspx?id=a5f152d0-0718-45de-bab0-a937015c2f35'],
            ['https://demo.ca.panopto.com/Panopto/Pages/Viewer.aspx?id=7604d3f5-c51d-4053-8c59-ab4400e0bbe6'],
            ['https://demo.cloud.panopto.eu/Panopto/Pages/Viewer.aspx?id=2eb0bf71-e051-4386-8aa3-0c7cf8a28135'],
            ['https://demo.ap.panopto.com/Panopto/Pages/Viewer.aspx?id=25329997-baa2-42cf-89c4-ab4400e080e3'],
        ];
    }

    /**
     * Test the Panopto Embed instantiation.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testCreateEmbedForUrl(string $urlToTest) {
        $parameters = [];
        parse_str(
            parse_url($urlToTest, PHP_URL_QUERY) ?? "",
            $parameters
        );

        $data = [
            'embedType' => PanoptoEmbed::TYPE,
            'domain' => parse_url($urlToTest, PHP_URL_HOST),
            'url' => $urlToTest,
            'sessionId' => $parameters['id'] ?? null,
        ];

        $panoptoEmbed = $this->factory->createEmbedForUrl($urlToTest);
        $embedData = $panoptoEmbed->jsonSerialize();

        $this->assertEquals($data, $embedData, 'Data can be used to render embed.');

        $embed = new PanoptoEmbed($embedData);
        $this->assertInstanceOf(PanoptoEmbed::class, $embed);
    }
}
