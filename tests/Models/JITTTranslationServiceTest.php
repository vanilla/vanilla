<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use DiscussionModel;
use Vanilla\Dashboard\Models\JITTTranslationService;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Forum\Models\TranslationPropertyModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\Dashboard\JITTTranslationTestTrait;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automated tests for JITTTranslationService
 */
class JITTTranslationServiceTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;
    use JITTTranslationTestTrait;
    use SchedulerTestTrait;

    public static $addons = ["subcommunities", "vanilla"];
    protected static $enabledLocales = ["vf_fr" => "fr", "vf_es" => "es", "vf_ru" => "ru"];

    private DiscussionModel $discussionModel;

    private JITTTranslationService $translationService;

    private translationModel $translationModel;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupJITTranslations();
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
        $this->translationService = \Gdn::getContainer()->get(JITTTranslationService::class);
        $this->translationModel = \Gdn::getContainer()->get(TranslationModel::class);
        $this->translationPropertyModel = \Gdn::getContainer()->get(TranslationPropertyModel::class);
        \Gdn::sql()->truncate("translation");
        \Gdn::sql()->truncate("translationProperty");
        $mockOpenAIClient = \Gdn::getContainer()->get(MockOpenAIClient::class);
        $mockOpenAIClient->addMockResponse(json_encode(["body" => "Test translation of these words."]), [
            "translation" => [
                "fr" => ["body" => "Test de traduction de ces mots."],
                "ru" => "Тест перевода этих слов.",
            ],
            "language" => ["en"],
        ]);

        $mockOpenAIClient->addMockResponse(
            json_encode(["body" => "Test translation of these words again.", "summary" => "Post Summary"]),
            [
                "translation" => [
                    "fr" => [
                        "body" => "Test de traduction de ces mots à nouveau.",
                        "summary" => "Résumé de l'article",
                    ],
                    "ru" => ["body" => "Тест перевода этих слов.", "summary" => "Резюме поста"],
                ],
                "language" => ["en"],
            ]
        );
        \Gdn::getContainer()->setInstance(OpenAIClient::class, $mockOpenAIClient);
    }

    /**
     * Test basic generation of translations
     *
     */
    public function testGenerationTranslations()
    {
        $recordID = 1;
        $recordType = "post";
        $toTranslate = [
            [
                "textToTranslate" => ["body" => "Test translation of these words."],
                "recordID" => $recordID,
                "recordType" => $recordType,
            ],
        ];

        $this->translationService->createTranslations($toTranslate);

        $translation = $this->translationPropertyModel->getTranslations([
            "recordID" => $recordID,
            "recordType" => $recordType,
            "propertyName" => "body",
            "locale" => "fr",
        ]);

        $this->assertCount(1, $translation);
        $this->assertSame("Test de traduction de ces mots.", $translation[0]["translation"]);
    }

    /**
     * Test call to JITTTranslationService with larger object.
     *
     */
    public function testGenerationTranslationLargerArray()
    {
        $toTranslateText = ["body" => "Test translation of these words again.", "summary" => "Post Summary"];
        $recordID = 1;
        $recordType = "post";
        $toTranslate = [
            [
                "textToTranslate" => $toTranslateText,
                "recordID" => $recordID,
                "recordType" => $recordType,
            ],
        ];
        $this->translationService->createTranslations($toTranslate);

        $translation = $this->translationPropertyModel->getTranslations([
            "recordID" => $recordID,
            "recordType" => $recordType,
            "propertyName" => "body",
            "locale" => "fr",
        ]);

        $this->assertCount(1, $translation);
        $this->assertSame("Test de traduction de ces mots à nouveau.", $translation[0]["translation"]);
    }

    /**
     * Test call to JITTTranslationService with larger object.
     *
     */
    public function testGenerationTranslationLargerArrayResume()
    {
        $toTranslateText = ["body" => "Test translation of these words again.", "summary" => "Post Summary"];

        $recordType = "post";
        $toTranslate = [
            [
                "textToTranslate" => $toTranslateText,
                "recordID" => 1,
                "recordType" => $recordType,
            ],
            [
                "textToTranslate" => $toTranslateText,
                "recordID" => 2,
                "recordType" => $recordType,
            ],
            [
                "textToTranslate" => $toTranslateText,
                "recordID" => 3,
                "recordType" => $recordType,
            ],
        ];

        $this->getLongRunner()->reset();
        $this->getLongRunner()->setMaxIterations(1);
        $action = new LongRunnerAction(JITTTranslationService::class, "generateTranslations", [$toTranslate]);
        $response = $this->getLongRunner()->runImmediately($action);

        $callbackPayload = $response->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertCount(1, $response->getSuccessIDs());

        $this->getLongRunner()->setMaxIterations(100);
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $response->getBody()["progress"]["successIDs"]);

        $translation = $this->translationPropertyModel->getTranslations([
            "recordID" => 3,
            "recordType" => $recordType,
            "propertyName" => "body",
            "locale" => "fr",
        ]);

        $this->assertCount(1, $translation);
        $this->assertSame("Test de traduction de ces mots à nouveau.", $translation[0]["translation"]);
    }

    /**
     * Test call to JITTTranslationService does not translate when config is turned off
     *
     * @dataProvider ConfigDataProvider
     */
    public function testGenerationOfTranslationsTurnedOffConfig(array $config, $exception = "")
    {
        $this->runWithConfig($config, function () use ($exception) {
            if ($exception != "") {
                $this->expectExceptionMessage($exception);
            }
            $toTranslateText = ["body" => "Test translation of these words."];
            $recordID = 2;
            $recordType = "post";
            $toTranslate = [
                [
                    "textToTranslate" => $toTranslateText,
                    "recordID" => $recordID,
                    "recordType" => $recordType,
                ],
            ];
            $this->translationService->createTranslations($toTranslate);

            $translation = $this->translationPropertyModel->getTranslations([
                "recordID" => $recordID,
                "recordType" => $recordType,
                "propertyName" => "body",
                "locale" => "fr",
            ]);

            $this->assertCount(0, $translation);
        });
    }

    /**
     * Provide data for testGenerationOfTranslationsTurnedOffConfig
     *
     * @return array
     */
    public function ConfigDataProvider(): array
    {
        $result = [
            "Languages not set" => [["JustInTimeTranslated.languageSelected" => []]],
            "Languages count 0" => [["JustInTimeTranslated.languageCount" => 0]],
            "Subcommunities turned off" => [["EnabledPlugins.subcommunities" => false]],
        ];
        return $result;
    }

    /**
     * @return LongRunner
     */
    protected function getLongRunner(): LongRunner
    {
        return \Gdn::getContainer()->get(LongRunner::class);
    }
}
