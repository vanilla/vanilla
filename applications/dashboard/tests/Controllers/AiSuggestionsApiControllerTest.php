<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard\Controllers;

use DiscussionModel;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class AiSuggestionsApiControllerTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    const VALID_SETTINGS = [
        "enabled" => true,
        "name" => "JarJarBinks",
        "icon" => "https://www.example.com/icon.png",
        "toneOfVoice" => "professional",
        "levelOfTech" => "advanced",
        "useBrEnglish" => true,
        "sources" => [
            "category" => [
                "enabled" => true,
                "exclusionIDs" => [1, 2, 3],
            ],
        ],
    ];

    public static $addons = ["qna"];

    private DiscussionModel $discussionModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        self::enableFeature("AISuggestions");
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
    }

    /**
     * Clear settings to test a bare configuration.
     *
     * @return void
     */
    protected function clearSettings(): void
    {
        \Gdn::config()->removeFromConfig("aiSuggestions");
        $assistantUserID = $this->userModel
            ->getWhere(["Email" => "ai-assistant@stub.vanillacommunity.example"])
            ->value("UserID");

        if (!empty($assistantUserID)) {
            $this->userModel->delete($assistantUserID);
        }
    }

    /**
     * Smoke test of the `GET /api/v2/ai-suggestions/settings` endpoint without existing settings.
     *
     * @return void
     */
    public function testGetSettings()
    {
        $this->clearSettings();
        $response = $this->api()->get("/ai-suggestions/settings");
        $this->assertTrue($response->isSuccessful());

        $settings = $response->getBody();
        $this->assertArrayHasKey("enabled", $settings);
        $this->assertFalse($settings["enabled"]);
    }

    /**
     * Test that the `GET /api/v2/ai-suggestions/settings` updates the settings and
     * creates a user with the configured name, icon, toneOfVoice, levelOfTech and useBrEnglish.
     *
     * @return void
     */
    public function testPatchSettings()
    {
        $this->clearSettings();
        $this->api()->patch("/ai-suggestions/settings", self::VALID_SETTINGS);

        $settings = $this->api()
            ->get("/ai-suggestions/settings")
            ->getBody();
        $this->assertEquals(self::VALID_SETTINGS, $settings);

        $assistantUserID = \Gdn::config()->get("aiSuggestions.userID");
        $this->assertIsInt($assistantUserID);

        $assistantUser = $this->userModel->getID($assistantUserID, DATASET_TYPE_ARRAY);
        $this->assertSame("JarJarBinks", $assistantUser["Name"]);
        $this->assertSame("https://www.example.com/icon.png", $assistantUser["Photo"]);

        $meta = \Gdn::getContainer()
            ->get(\UserMetaModel::class)
            ->getUserMeta($assistantUserID, "aiAssistant.%", prefix: "aiAssistant.");
        $this->assertSame("professional", $meta["toneOfVoice"]);
        $this->assertSame("advanced", $meta["levelOfTech"]);
        $this->assertSame("1", $meta["useBrEnglish"]);
    }

    /**
     * Smoke test of getting data to render suggestion sources.
     * This just tests that we have a successful response and `category` is one of the built-in sources.
     *
     * @return void
     */
    public function testGetSources()
    {
        $response = $this->api()->get("/ai-suggestions/sources");
        $this->assertTrue($response->isSuccessful());

        $sources = $response->getBody();
        $this->assertArrayHasKey("properties", $sources);
        $this->assertIsArray($sources["properties"]);
        $this->assertArrayHasKey("category", $sources["properties"]);
    }

    /**
     * Test that a forbidden exception is thrown when trying to dismiss a discussion belonging to another user.
     *
     * @return void
     */
    public function testForbiddenExceptionForDismissingAnotherDiscussionSuggestion()
    {
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You are not allowed to use suggestions.");

        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2.
        $this->runWithUser(function () use ($discussion) {
            $this->api()->post("/ai-suggestions/dismiss", [
                "discussionID" => $discussion["discussionID"],
                "suggestionIDs" => [0, 1, 2],
            ]);
        }, $this->createUser());
    }

    /**
     * Test that suggestions can be dismissed if we have the `curation.manage` permission.
     *
     * @return void
     */
    public function testCanDismissSuggestionWithCorrectPermission()
    {
        $this->expectNotToPerformAssertions();

        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2 with proper permissions.
        $this->runWithPermissions(
            function () use ($discussion) {
                $this->api()->post("/ai-suggestions/dismiss", [
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 1, 2],
                ]);
            },
            ["curation.manage" => true]
        );
    }

    /**
     * Test that suggestions can be dismissed if the discussion belongs to the current user.
     *
     * @return void
     */
    public function testDismissDiscussionSuggestionForOwnDiscussion()
    {
        $this->expectNotToPerformAssertions();

        $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $this->api()->post("/ai-suggestions/dismiss", [
                "discussionID" => $discussion["discussionID"],
                "suggestionIDs" => [0, 1, 2],
            ]);
        }, $this->createUser());
    }

    /**
     * Test that a forbidden exception is thrown when trying to restore a discussion belonging to another user.
     *
     * @return void
     */
    public function testForbiddenExceptionForRestoringAnotherDiscussionSuggestion()
    {
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You are not allowed to use suggestions.");

        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2.
        $this->runWithUser(function () use ($discussion) {
            $this->api()->post("/ai-suggestions/restore", [
                "discussionID" => $discussion["discussionID"],
            ]);
        }, $this->createUser());
    }

    /**
     * Test that suggestions can be restored if we have the `curation.manage` permission.
     *
     * @return void
     */
    public function testCanRestoreSuggestionWithCorrectPermission()
    {
        $this->expectNotToPerformAssertions();

        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2 with proper permissions.
        $this->runWithPermissions(
            function () use ($discussion) {
                $this->api()->post("/ai-suggestions/restore", [
                    "discussionID" => $discussion["discussionID"],
                ]);
            },
            ["curation.manage" => true]
        );
    }

    /**
     * Test that suggestions can be restored if the discussion belongs to the current user.
     *
     * @return void
     */
    public function testRestoreDiscussionSuggestionForOwnDiscussion()
    {
        $this->expectNotToPerformAssertions();

        $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $this->api()->post("/ai-suggestions/restore", [
                "discussionID" => $discussion["discussionID"],
            ]);
        }, $this->createUser());
    }

    /**
     * Test generation of suggestions
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     */
    public function testGenerationOfSuggestion()
    {
        $this->setupConfigs();
        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertCount(3, $suggestions);
        $this->assertSame($suggestions[0], [
            "format" => "Vanilla",
            "type" => "mockSuggestion",
            "id" => 0,
            "url" => "someplace.com/here",
            "title" => "answer 1",
            "summary" => "This is how you do this.",
            "hidden" => false,
        ]);

        $createdComments = $this->runWithUser(function () use ($discussion) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 2],
                ])
                ->getBody();

            return $createdComments;
        }, $discussion["insertUserID"]);
        $this->assertCount(2, $createdComments);
        $this->assertSame(\QnaModel::ACCEPTED, $createdComments[0]["qnA"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedDiscussion["statusID"]);
    }

    /**
     * Test generation of suggestions, accepting them and cancelling them
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     */
    public function testRemoveAcceptedSuggestions()
    {
        $this->setupConfigs();

        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertCount(3, $suggestions);
        $this->assertSame($suggestions[0], [
            "format" => "Vanilla",
            "type" => "mockSuggestion",
            "id" => 0,
            "url" => "someplace.com/here",
            "title" => "answer 1",
            "summary" => "This is how you do this.",
            "hidden" => false,
        ]);

        $createdComments = $this->runWithUser(function () use ($discussion) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 2],
                ])
                ->getBody();

            return $createdComments;
        }, $discussion["insertUserID"]);
        $this->assertCount(2, $createdComments);
        $this->assertSame(\QnaModel::ACCEPTED, $createdComments[0]["qnA"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedDiscussion["statusID"]);

        $removeStatus = $this->runWithUser(function () use ($discussion) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/remove-accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 2],
                ])
                ->getBody();

            return $createdComments;
        }, $discussion["insertUserID"]);

        $this->assertSame(["removed" => true], $removeStatus);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_UNANSWERED, $updatedDiscussion["statusID"]);
    }

    /**
     * Test accept of suggestions exception
     *
     * @dataProvider ConfigDataProvider
     */
    public function testAcceptSuggestionTurnedOffConfig(array $config)
    {
        $this->expectExceptionMessage("AI Suggestions are not enabled.");

        $this->runWithConfig($config, function () {
            $discussion = $this->createDiscussion(["type" => "question"]);

            $this->runWithUser(function () use ($discussion) {
                $createdComments = $this->api()
                    ->post("/ai-suggestions/accept-suggestion", [
                        "allSuggestions" => false,
                        "discussionID" => $discussion["discussionID"],
                        "suggestionIDs" => [0, 2],
                    ])
                    ->getBody();

                return $createdComments;
            }, $discussion["insertUserID"]);
        });
    }

    /**
     * Test remove of suggestions exception
     *
     * @dataProvider ConfigDataProvider
     */
    public function testRemoveSuggestionTurnedOffConfig(array $config)
    {
        $this->expectExceptionMessage("AI Suggestions are not enabled.");

        $this->runWithConfig($config, function () {
            $discussion = $this->createDiscussion(["type" => "question"]);

            $this->runWithUser(function () use ($discussion) {
                $createdComments = $this->api()
                    ->post("/ai-suggestions/remove-accept-suggestion", [
                        "allSuggestions" => false,
                        "discussionID" => $discussion["discussionID"],
                        "suggestionIDs" => [0, 2],
                    ])
                    ->getBody();

                return $createdComments;
            }, $discussion["insertUserID"]);
        });
    }

    /**
     * Test accept of suggestions exception when other user trying to accept not their own question
     *
     */
    public function testAcceptSuggestionOtherUser()
    {
        $this->expectExceptionMessage("You are not allowed to use suggestions.");

        $discussion = $this->createDiscussion(["type" => "question"]);
        $newMember = $this->createUser();
        $this->runWithUser(function () use ($discussion) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 2],
                ])
                ->getBody();

            return $createdComments;
        }, $this->lastUserID);
    }

    /**
     * Test remove of suggestions exception when other user trying to accept not their own question
     *
     */
    public function testRemoveSuggestionOtherUser()
    {
        $this->expectExceptionMessage("You are not allowed to use suggestions.");

        $discussion = $this->createDiscussion(["type" => "question"]);
        $newMember = $this->createUser();
        $this->runWithUser(function () use ($discussion) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/remove-accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [0, 2],
                ])
                ->getBody();

            return $createdComments;
        }, $this->lastUserID);
    }

    /**
     * Test that a forbidden exception is thrown when trying to change suggestionsVisibility a discussion belonging to another user.
     *
     * @return void
     */
    public function testForbiddenExceptionForSuggestionsVisibilityAnotherDiscussionSuggestion()
    {
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("You are not allowed to use suggestions.");

        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2.
        $this->runWithUser(function () use ($discussion) {
            $this->api()->post("/ai-suggestions/suggestions-visibility", [
                "discussionID" => $discussion["discussionID"],
                "visible" => false,
            ]);
        }, $this->createUser());
    }

    /**
     * Test that suggestions can be suggestionsVisibility if we have the `curation.manage` permission.
     *
     * @return void
     */
    public function testCanSuggestionsVisibilityWithCorrectPermission()
    {
        $this->expectNotToPerformAssertions();
        $this->setupConfigs();
        // Create a discussion as user 1.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $this->createUser());

        // Try to dismiss the discussion's suggestions as user 2 with proper permissions.
        $this->runWithPermissions(
            function () use ($discussion) {
                $this->api()->post("/ai-suggestions/suggestions-visibility", [
                    "discussionID" => $discussion["discussionID"],
                    "visible" => false,
                ]);
            },
            ["curation.manage" => true]
        );
    }

    /**
     * Test that suggestions can be suggestionsVisibility if the discussion belongs to the current user.
     *
     * @return void
     */
    public function testsuggestionsVisibilityDiscussionForOwnDiscussion()
    {
        $this->setupConfigs();
        $discussionID = $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $this->api()->post("/ai-suggestions/suggestions-visibility", [
                "discussionID" => $discussion["discussionID"],
                "visible" => false,
            ]);
            return $discussion["discussionID"];
        }, $this->createUser());

        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $this->assertEquals(false, $discussion["Attributes"]["visibleSuggestions"]);
    }

    /**
     * Provide data for config cversions
     *
     * @return array
     */
    public function ConfigDataProvider(): array
    {
        $result = [
            "Feature Flag Off" => [["Feature.AISuggestions.Enabled" => false]],
            "Feature turned off" => [["aiSuggestions" => ["enabled" => false]]],
        ];
        return $result;
    }

    public function setupConfigs()
    {
        $this->createUser();
        \Gdn::config()->saveToConfig([
            "Feature.AISuggestions.Enabled" => true,
            "aiSuggestions" => [
                "enabled" => true,
                "userID" => $this->lastUserID,
                "sources" => ["mockSuggestion" => ["enabled" => true]],
            ],
        ]);
    }
}
