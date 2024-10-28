<?php
namespace VanillaTests\Library\Vanilla\EmbeddedContent;

use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Forum\EmbeddedContent\Factories\DiscussionEmbedFactory;
use Vanilla\Logging\ErrorLogger;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\TestLoggerTrait;

class EmbedServiceTest extends SiteTestCase
{
    use TestLoggerTrait;
    use CommunityApiTestTrait;

    private EmbedService $embedService;

    /**
     * Do some pre-test setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->embedService = self::container()->get(EmbedService::class);
    }

    /**
     * Test base64 image embed error.
     */
    public function testImageEmbedError()
    {
        // Make sure the image embed is registered.
        $this->embedService->registerEmbed(ImageEmbed::class, ImageEmbed::TYPE);

        $imageData = file_get_contents(PATH_ROOT . "/tests/fixtures/apple.jpg");
        $base64Image = "data:image/jpeg;base64," . base64_encode($imageData);

        $embedData = [
            "embedType" => "image",
            "url" => $base64Image,
            "name" => "",
            "height" => "200",
            "width" => "200",
        ];

        $this->runWithConfig([ErrorLogger::CONF_LOG_NOTICES => true], function () use ($embedData) {
            $this->embedService->createEmbedFromData($embedData);
            $this->assertErrorLogMessage("Validation error while instantiating embed type image");
        });
    }

    /**
     * Test that quote embeds are not cached.
     *
     * @return void
     */
    public function testQuoteEmbedsNotCached()
    {
        // Make sure quote embed is registered
        $this->embedService->registerEmbed(QuoteEmbed::class, QuoteEmbed::TYPE);
        $this->embedService->registerFactory($this->container()->get(DiscussionEmbedFactory::class));

        // Create a discussion to quote
        $discussion = $this->createDiscussion(["body" => "test"]);

        // Call media endpoint to get quote embed.
        $this->api()->post("media/scrape", ["url" => $discussion["url"]]);

        // Update the discussion
        $this->api()->patch("discussions/{$discussion["discussionID"]}", ["body" => "updated"]);

        // Call media endpoint again to verify result is not cached.
        $embed = $this->api()
            ->post("media/scrape", ["url" => $discussion["url"]])
            ->getBody();
        $this->assertEquals("updated", $embed["bodyRaw"]);
    }
}
