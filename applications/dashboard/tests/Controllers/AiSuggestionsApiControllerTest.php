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
use Vanilla\Dashboard\AiSuggestionModel;
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
            "mockSuggestion" => [
                "enabled" => true,
                "exclusionIDs" => [1, 2, 3],
            ],
        ],
    ];

    public static $addons = ["qna"];

    private DiscussionModel $discussionModel;
    private AiSuggestionModel $aiSuggestionModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        self::enableFeature("AISuggestions");
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
        $this->aiSuggestionModel = $this->container()->get(AiSuggestionModel::class);
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

        $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussion["discussionID"]);
        $this->assertCount(3, $suggestions);
        $this->assertArraySubsetRecursive(
            [
                "format" => "Vanilla",
                "type" => "mockSuggestion",
                "url" => "someplace.com/here",
                "title" => "answer 1",
                "summary" => "This is how you do this.",
                "hidden" => 0,
            ],
            $suggestions[0]
        );

        $createdComments = $this->runWithUser(function () use ($discussion, $suggestions) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [$suggestions[0]["aiSuggestionID"], $suggestions[2]["aiSuggestionID"]],
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

        $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussion["discussionID"]);
        $this->assertCount(3, $suggestions);
        $this->assertArraySubsetRecursive(
            [
                "format" => "Vanilla",
                "type" => "mockSuggestion",
                "url" => "someplace.com/here",
                "title" => "answer 1",
                "summary" => "This is how you do this.",
                "hidden" => 0,
            ],
            $suggestions[0]
        );

        $createdComments = $this->runWithUser(function () use ($discussion, $suggestions) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [$suggestions[0]["aiSuggestionID"], $suggestions[2]["aiSuggestionID"]],
                ])
                ->getBody();

            return $createdComments;
        }, $discussion["insertUserID"]);
        $this->assertCount(2, $createdComments);
        $this->assertSame(\QnaModel::ACCEPTED, $createdComments[0]["qnA"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedDiscussion["statusID"]);

        $removeStatus = $this->runWithUser(function () use ($discussion, $suggestions) {
            $createdComments = $this->api()
                ->post("/ai-suggestions/remove-accept-suggestion", [
                    "allSuggestions" => false,
                    "discussionID" => $discussion["discussionID"],
                    "suggestionIDs" => [$suggestions[0]["aiSuggestionID"], $suggestions[2]["aiSuggestionID"]],
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
        $this->assertEquals(false, $discussion["Attributes"]["showSuggestions"]);
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

    /**
     * Tests the /ai-suggestions/generate endpoint to regenerate suggestions.
     *
     * @depends testPatchSettings
     * @return void
     */
    public function test_generateSuggestions()
    {
        // Quick test that suggestions are generated when the question is first posted.
        $discussionID = $this->createDiscussion(["type" => "question"])["discussionID"];
        $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussionID);
        $this->assertNotEmpty($suggestions);
        $this->assertSame("mockSuggestion", $suggestions[0]["type"]);

        // Null out attributes.
        $this->discussionModel->update(["Attributes" => null, ["DiscussionID" => $discussionID]]);

        // Make sure attributes were actually cleared.
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $this->assertNull($discussion["Attributes"]);

        // Now call generate endpoint and check that suggestions are regenerated.
        $response = $this->api()->put("/ai-suggestions/generate", ["discussionID" => $discussionID]);
        $this->assertTrue($response->isSuccessful());
        $this->assertContains("mockSuggestion", $response->getBody()["progress"]["successIDs"]);
        $suggestions = $this->aiSuggestionModel->getByDiscussionID($discussionID);
        $this->assertNotEmpty($suggestions);
        $this->assertSame("mockSuggestion", $suggestions[0]["type"]);
    }
}
