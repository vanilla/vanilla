<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\Embeds\MuralEmbed;
use Vanilla\EmbeddedContent\Factories\MuralEmbedFactory;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the Mural embed factory.
 */
class MuralEmbedFactoryTest extends MinimalContainerTestCase
{
    /** @var MuralEmbedFactory */
    private $factory;

    /**
     * Set the factory.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->factory = new MuralEmbedFactory();
    }

    /**
     * Test that all domain types are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testSupportedUrls(string $urlToTest)
    {
        $this->assertTrue($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * Test a few domain types that are unsupported.
     *
     * @param string $urlToTest
     * @dataProvider unsupportedUrlsProvider
     */
    public function testUnsupportedUrls(string $urlToTest)
    {
        $this->assertFalse($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * Return an array of supported urls.
     *
     * @return array
     */
    public function supportedUrlsProvider(): array
    {
        return [
            ["https://app.mural.co/embed/9c9b6dae-8c4b-4b3e-8811-24e8bd2ee5c9"],
            ["https://app.mural.com/embed/9c9b6dae-8c4b-4b3e-8811-24e8bd2ee5c9"],
        ];
    }

    /**
     * Return an array of unsupported urls.
     *
     * @return array
     */
    public function unsupportedUrlsProvider(): array
    {
        return [
            ["https://players.brightcove.net/1160438696001/hUGC1VhwM_default/index.html?videoId=5842888344001"],
            ["https://www.instagram.com/p/By_Et7NnKgL"],
        ];
    }

    /**
     * Test the Mural Embed instantiation.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testCreateEmbedForUrl(string $urlToTest)
    {
        $data = [
            "embedType" => MuralEmbed::TYPE,
            "url" => $urlToTest,
        ];

        $MuralEmbed = $this->factory->createEmbedForUrl($urlToTest);
        $embedData = $MuralEmbed->jsonSerialize();

        $this->assertEquals($data, $embedData, "Data can be used to render embed.");

        $embed = new MuralEmbed($embedData);
        $this->assertInstanceOf(MuralEmbed::class, $embed);
    }
}
