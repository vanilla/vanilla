<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace APIv2;

use UserMetaModel;
use Vanilla\Web\PrivateCommunityMiddleware;
use VanillaTests\Dashboard\Controllers\AiSuggestionsApiControllerTest;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/users endpoints.
 */
class UsersAISuggestionsTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /**
     * @var \Gdn_Configuration
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/users";
        $this->resourceName = "user";
        $this->record = [
            "name" => null,
            "email" => null,
        ];
        $this->sortFields = ["dateInserted", "dateLastActive", "name", "userID", "points", "countPosts"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Disable email before running tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->configuration = static::container()->get("Config");
        $this->configuration->set("Garden.Email.Disabled", true);

        /* @var PrivateCommunityMiddleware $middleware */
        $middleware = static::container()->get(PrivateCommunityMiddleware::class);
        $middleware->setIsPrivate(false);
        $this->resetTable("profileField");
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test that the SuggestAnswers field can be patched.
     */
    public function testPatchSuggestAnswersField()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => false]);
        $this->assertArrayNotHasKey("suggestAnswers", $response->getBody());
        $this->runWithConfig(
            [
                "Feature.AISuggestions.Enabled" => true,
                "aiSuggestions.enabled" => true,
            ],
            function () use ($user) {
                $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => false]);
                $this->assertSame(false, $response->getBody()["suggestAnswers"]);
                $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => true]);
                $this->assertSame(true, $response->getBody()["suggestAnswers"]);
            }
        );
    }

    /**
     * Test that patching SuggestAnswers field without accepting cookies throws an error.
     */
    public function testPatchUserWithSuggestAnswersAndNoCookie()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);

        $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => false]);
        $this->assertArrayNotHasKey("suggestAnswers", $response->getBody());

        $this->runWithConfig(
            [
                "Feature.AISuggestions.Enabled" => true,
                "aiSuggestions.enabled" => true,
            ],
            function () use ($user) {
                $this->api()->patch("/ai-suggestions/settings", AiSuggestionsApiControllerTest::VALID_SETTINGS);
                $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => true]);
                $this->assertSame(true, $response->getBody()["suggestAnswers"]);

                \Gdn::userMetaModel()->setUserMeta($user["userID"], UserMetaModel::ANONYMIZE_DATA_USER_META, 1);
                $response = $this->api()->patch("/users/{$user["userID"]}", [
                    "SuggestAnswers" => true,
                    "private" => true,
                ]);
                $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => false]);
                $this->assertSame(false, $response->getBody()["suggestAnswers"]);
            }
        );
    }

    /**
     * Test that patching SuggestAnswers field without accepting cookies throws an error.
     */
    public function testPatchSuggestAnswersFieldError()
    {
        $user = $this->createUser(["name" => __FUNCTION__]);
        \Gdn::userMetaModel()->setUserMeta($user["userID"], UserMetaModel::ANONYMIZE_DATA_USER_META, 1);
        $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => false]);
        $this->assertArrayNotHasKey("suggestAnswers", $response->getBody());
        $this->expectExceptionMessage(
            AiSuggestionsApiControllerTest::VALID_SETTINGS["name"] .
                " Answers is not available if you have not accepted cookies."
        );

        $this->runWithConfig(
            [
                "Feature.AISuggestions.Enabled" => true,
                "aiSuggestions.enabled" => true,
            ],
            function () use ($user) {
                $this->api()->patch("/ai-suggestions/settings", AiSuggestionsApiControllerTest::VALID_SETTINGS);
                $response = $this->api()->patch("/users/{$user["userID"]}", ["SuggestAnswers" => true]);
                $this->assertSame(true, $response->getBody()["suggestAnswers"]);
            }
        );
    }

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    protected function triggerDirtyRecords()
    {
        $this->resetTable("dirtyRecord");
        $user = $this->createUser();
        $this->givePoints($user["userID"], 10);
    }

    /**
     * Get the resource type.
     *
     * @return array
     */
    protected function getResourceInformation(): array
    {
        return [
            "resourceType" => "user",
            "primaryKey" => "userID",
        ];
    }
}
