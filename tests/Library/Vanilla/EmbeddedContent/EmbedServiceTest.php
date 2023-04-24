<?php
namespace VanillaTests\Library\Vanilla\EmbeddedContent;

use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use VanillaTests\SiteTestCase;
use VanillaTests\TestLoggerTrait;

class EmbedServiceTest extends SiteTestCase
{
    use TestLoggerTrait;

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

        $this->embedService->createEmbedFromData($embedData);
        $this->assertErrorLogMessage("Validation error while instantiating embed type image");
    }
}
