<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use Gdn;
use MachineTranslation\Services\GptTranslationService;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automated tests for GPT TranslationService
 */
class GptTranslationServiceTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;
    use GptTranslationTestTrait;
    use SchedulerTestTrait;

    public static $addons = ["vanilla"];
    private GptTranslationService $translationService;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupGptTranslations();

        $mockOpenAIClient = Gdn::getContainer()->get(MockOpenAIClient::class);
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
        $this->container()->setInstance(OpenAIClient::class, $mockOpenAIClient);
        $this->translationService = $this->container()->get(GptTranslationService::class);
    }

    /**
     * Test basic generation of translations
     */
    public function testGenerationTranslations()
    {
        $translation = $this->translationService->translate(["body" => "Test translation of these words."], "en");
        $this->assertCount(1, $translation["translation"]["fr"]);
        $this->assertSame("Test de traduction de ces mots.", $translation["translation"]["fr"]["body"]);
        $this->assertSame("Тест перевода этих слов.", $translation["translation"]["ru"]);
    }

    /**
     * Test call to GTP TranslationService with larger object.
     */
    public function testGenerationTranslationLargerArray()
    {
        $toTranslateText = ["body" => "Test translation of these words again.", "summary" => "Post Summary"];
        $translation = $this->translationService->translate($toTranslateText, "en");

        $this->assertCount(2, $translation["translation"]["ru"]);
        $this->assertCount(2, $translation["translation"]["fr"]);
        $this->assertSame("Test de traduction de ces mots à nouveau.", $translation["translation"]["fr"]["body"]);
        $this->assertSame("Résumé de l'article", $translation["translation"]["fr"]["summary"]);
        $this->assertSame("Тест перевода этих слов.", $translation["translation"]["ru"]["body"]);
        $this->assertSame("Резюме поста", $translation["translation"]["ru"]["summary"]);
    }

    /**
     * Test call to GptModel does not translate when config is turned off
     *
     * @dataProvider ConfigDataProvider
     */
    public function testGenerationOfTranslationsTurnedOffConfig(array $config): void
    {
        $config = $config + [
            "enabled" => true,
            "locales" => ["vf_fr" => "fr", "vf_en" => "en", "vf_ru" => "ru"],
            "maxLocale" => 3,
        ];

        $this->runWithConfig(["MachineTranslation" => ["translationServices" => ["Gpt" => [$config]]]], function () {
            $mockClient = $this->container()->get(MockOpenAIClient::class);
            $translationService = new GptTranslationService(
                $this->container()->get(ConfigurationInterface::class),
                $mockClient
            );
            $this->assertFalse($translationService->isEnabled());
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
            "Languages not set" => [["languageSelected" => []]],
            "Languages count 0" => [["languageCount" => 0]],
            "Service turned off" => [["Enabled" => false]],
        ];
        return $result;
    }
}
