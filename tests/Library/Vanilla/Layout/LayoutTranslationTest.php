<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Layout;

use Gdn;
use Vanilla\Dashboard\Addon\LayoutEventHandler;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Forum\Models\TranslationPropertyModel;
use Vanilla\Layout\LayoutModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\Dashboard\GptTranslationTestTrait;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test custom layout translation.
 */
class LayoutTranslationTest extends SiteTestCase
{
    use GptTranslationTestTrait;
    use SchedulerTestTrait;

    /**
     * @var string[]
     */
    protected static $enabledLocales = [
        "vf_fr" => "fr",
        "vf_es" => "es",
        "vf_ru" => "ru",
        "vf_it" => "it",
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupGptTranslations();

        $this->layoutModel = $this->container()->get(LayoutModel::class);
        $this->translationModel = $this->container()->get(TranslationModel::class);
        $this->translationPropertyModel = $this->container()->get(TranslationPropertyModel::class);
        Gdn::sql()->truncate("translation");
        Gdn::sql()->truncate("translationProperty");
        $mockOpenAIClient = $this->container()->get(MockOpenAIClient::class);

        // We will mock the full response from OpenAI.
        $mockOpenAIClient->addMockResponse(json_encode("Featured Categories"), [
            "translation" => [
                "fr" => "lorem ipsum",
                "ru" => "lorem ipsum",
                "it" => "lorem ipsum",
            ],
            "language" => ["en"],
        ]);
        $mockOpenAIClient->addMockResponse(json_encode("Recent Discussions"), [
            "translation" => [
                "fr" => "lorem ipsum",
                "ru" => "lorem ipsum",
                "it" => "lorem ipsum",
            ],
            "language" => ["en"],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("Welcome!"), [
            "translation" => [
                "fr" => "lorem ipsum",
                "ru" => "lorem ipsum",
                "it" => "lorem ipsum",
            ],
            "language" => ["en"],
        ]);

        $mockOpenAIClient->addMockResponse(
            json_encode("It looks like you're new here. Sign in or register to get started."),
            [
                "translation" => [
                    "fr" => "lorem ipsum",
                    "ru" => "lorem ipsum",
                    "it" => "lorem ipsum",
                ],
                "language" => ["en"],
            ]
        );
        $mockOpenAIClient->addMockResponse(json_encode("Use this space to add"), [
            "translation" => [
                "fr" => "",
                "ru" => "",
                "it" => "",
            ],
            "language" => ["en"],
        ]);

        $mockOpenAIClient->addMockResponse(json_encode("Quick Links"), [
            "translation" => [
                "fr" => "lorem ipsum",
                "ru" => "lorem ipsum",
                "it" => "lorem ipsum",
            ],
            "language" => ["en"],
        ]);
        $mockOpenAIClient->addMockResponse(json_encode("Customer Spotlight"), [
            "translation" => [
                "fr" => "lorem ipsum",
                "ru" => "lorem ipsum",
                "it" => "lorem ipsum",
            ],
            "language" => ["en"],
        ]);

        $this->container()->setInstance(OpenAIClient::class, $mockOpenAIClient);
        $this->communityTranslationModel = $this->container()->get(CommunityMachineTranslationModel::class);
    }

    /**
     * Test translate a layout.
     *
     * @return void
     */
    public function testLayoutTranslation(): void
    {
        $homeLayout = self::getHomeLayout();
        $homeLayout = json_decode($homeLayout, true);
        $layout = $this->api()->post("layouts", [
            "name" => __FUNCTION__,
            "layoutViewType" => "home",
            "layout" => $homeLayout,
        ]);
        $translatedLayout = self::getTranslatedHomeLayout();
        $this->assertLayoutTranslated($layout["layoutID"], "fr", $translatedLayout);

        $records = $this->translationModel->getTranslationByRecord("layout", $layout["layoutID"], "fr");
        $this->assertCount(8, $records);

        // Make sure that the empty string is not translated but still set as empty.
        $this->assertEmpty($records[6]["translation"]);

        // Make sure that the layout is translated when the locale is set with CMT enabled.
        Gdn::locale()->set("fr");
        $result = $this->layoutModel->getByID($layout["layoutID"]);
        $this->assertEquals(json_decode($translatedLayout, true), $result["layout"]);
    }

    /**
     * Test that editing a layout will trigger the translation.
     *
     * @return void
     */
    public function testEditingLayout(): void
    {
        $homeLayout = self::getHomeLayout();
        $homeLayout = json_decode($homeLayout, true);
        $layout = $this->api()->post("layouts", [
            "name" => __FUNCTION__,
            "layoutViewType" => "home",
            "layout" => [],
        ]);

        $this->api()->patch("layouts/{$layout["layoutID"]}", [
            "layout" => $homeLayout,
        ]);

        $translatedLayout = self::getTranslatedHomeLayout();
        $this->assertLayoutTranslated($layout["layoutID"], "fr", $translatedLayout);

        $records = $this->translationModel->getTranslationByRecord("layout", $layout["layoutID"], "fr");
        $this->assertCount(8, $records);

        // Make sure that the empty string is not translated but still set as empty.
        $this->assertEmpty($records[6]["translation"]);
    }

    /**
     * Test that the layout translation job doesn't run when the layout does not exist.
     *
     * @return void
     */
    public function testTranslationLayoutDontExist(): void
    {
        $action = new LongRunnerAction(LayoutEventHandler::class, "translateLayouts", [0, ["fr", "ru", "es", "it"]]);
        $this->getLongRunner()->reset();
        $response = $this->getLongRunner()->runImmediately($action);

        $this->assertTrue($response->isCompleted());
        $this->assertCount(0, $response->getSuccessIDs());
    }

    /**
     * Test that the layout translation job doesn't run when there are no valid locale.
     *
     * @return void
     */
    public function testTranslationLayoutNoLocale(): void
    {
        $layout = $this->api()->post("layouts", [
            "name" => __FUNCTION__,
            "layoutViewType" => "home",
            "layout" => [],
        ]);
        $action = new LongRunnerAction(LayoutEventHandler::class, "translateLayouts", [$layout["layoutID"], []]);
        $this->getLongRunner()->reset();
        $response = $this->getLongRunner()->runImmediately($action);

        $this->assertTrue($response->isCompleted());
        $this->assertCount(0, $response->getSuccessIDs());
    }

    /**
     * Test that the long runner will pick up where it left.
     *
     * @return void
     */
    public function testLongRunnerResume(): void
    {
        $homeLayout = self::getHomeLayout();
        $homeLayout = json_decode($homeLayout, true);
        $layout = $this->api()->post("layouts", [
            "name" => __FUNCTION__,
            "layoutViewType" => "home",
            "layout" => $homeLayout,
        ]);

        $this->resetTable("translation");
        $this->getLongRunner()->reset();
        $this->getLongRunner()->setMaxIterations(1);
        $action = new LongRunnerAction(LayoutEventHandler::class, "translateLayouts", [
            $layout["layoutID"],
            ["fr", "ru", "es", "it"],
        ]);
        $response = $this->getLongRunner()->runImmediately($action);

        $callbackPayload = $response->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertCount(1, $response->getSuccessIDs());

        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($callbackPayload);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $response->getBody()["progress"]["successIDs"]);
    }

    /**
     * Assert that the layout is translated.
     *
     * @param int $layoutID
     * @param string $locale
     * @param string $translatedLayout
     * @return void
     */
    public function assertLayoutTranslated(int $layoutID, string $locale, string $translatedLayout): void
    {
        $translatedLayout = json_decode($translatedLayout, true);
        $translation = $this->translationPropertyModel->getTranslations([
            "recordID" => $layoutID,
            "recordType" => "layout",
            "propertyName" => "layout",
            "locale" => $locale,
        ]);

        $this->assertCount(1, $translation);
        $this->assertEquals($translatedLayout, json_decode($translation[0]["translation"], true));
    }

    /**
     * Get the home layout.
     *
     * @return string
     */
    private static function getHomeLayout(): string
    {
        $layout = file_get_contents(PATH_ROOT . "/tests/Library/Vanilla/Layout/homeLayout.json");
        return $layout;
    }

    /**
     * Get the translated home layout.
     *
     * @return string
     */
    private static function getTranslatedHomeLayout(): string
    {
        $layout = file_get_contents(PATH_ROOT . "/tests/Library/Vanilla/Layout/translatedHomeLayout.json");
        return $layout;
    }
}
