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
        ];
    }

    /**
     * Test the Panopto Embed instantiation.
     */
    public function testCreateEmbedForUrl() {
        $urlsToTest = $this->supportedUrlsProvider();

        foreach ($urlsToTest as $urlToTest) {
            $urlToTest = $urlToTest[0];

            $parameters = [];
            parse_str(
                parse_url($urlToTest, PHP_URL_QUERY) ?? "",
                $parameters
            );

            $data = [
                'embedType' => PanoptoEmbed::TYPE,
                'domain' => parse_url($urlToTest, PHP_URL_HOST),
                'url' => $urlToTest,
                'height' => 360,
                'width' => 640,
                'sessionId' => $parameters['id'] ?? null,
            ];

            $panoptoEmbed = $this->factory->createEmbedForUrl($urlToTest);
            $embedData = $panoptoEmbed->jsonSerialize();

            $this->assertEquals($data, $embedData, 'Data can be used to render embed.');

            $embed = new PanoptoEmbed($embedData);
            $this->assertInstanceOf(PanoptoEmbed::class, $embed);
        }
    }
}
