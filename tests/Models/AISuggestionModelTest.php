<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Models;

use DiscussionModel;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automated tests for AiSuggestionSourceService
 */
class AISuggestionModelTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;

    public static $addons = ["qna"];

    private DiscussionModel $discussionModel;

    private AiSuggestionSourceService $suggestionSourceService;

    private array $assistantUser;

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $assistantUser = $this->createUser();
        \Gdn::config()->saveToConfig([
            "Feature.AISuggestions.Enabled" => true,
            "aiSuggestions" => [
                "enabled" => true,
                "userID" => $assistantUser["userID"],
                "sources" => ["mockSuggestion" => ["enabled" => true]],
            ],
        ]);
        $this->assistantUser = $assistantUser;
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
        $this->suggestionSourceService = $this->container()->get(AiSuggestionSourceService::class);
    }

    /**
     * Test generation of suggestions
     *
     * @throws ClientException Not Applicable.
     * @throws ValidationException Not Applicable.
     * @throws NoResultsException Not Applicable.
     */
    public function testGenerationOfSuggestions()
    {
        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertCount(3, $suggestions);
        $this->assertSame(
            [
                "format" => "Vanilla",
                "type" => "mockSuggestion",
                "id" => 0,
                "url" => "someplace.com/here",
                "title" => "answer 1",
                "summary" => "This is how you do this.",
                "hidden" => false,
            ],
            $suggestions[0]
        );

        $createdComments = $this->runWithUser(function () use ($discussion) {
            $suggestion = $this->container()->get(AiSuggestionSourceService::class);
            return $suggestion->createComments($discussion["discussionID"], [0, 2]);
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
        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertCount(3, $suggestions);
        $this->assertSame(
            [
                "format" => "Vanilla",
                "type" => "mockSuggestion",
                "id" => 0,
                "url" => "someplace.com/here",
                "title" => "answer 1",
                "summary" => "This is how you do this.",
                "hidden" => false,
            ],
            $suggestions[0]
        );

        $createdComments = $this->runWithUser(function () use ($discussion) {
            $suggestion = $this->container()->get(AiSuggestionSourceService::class);
            return $suggestion->createComments($discussion["discussionID"], [0, 2]);
        }, $discussion["insertUserID"]);
        $this->assertCount(2, $createdComments);
        $this->assertSame(\QnaModel::ACCEPTED, $createdComments[0]["qnA"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedDiscussion["statusID"]);

        $removeStatus = $this->runWithUser(function () use ($discussion) {
            $suggestion = $this->container()->get(AiSuggestionSourceService::class);
            return $suggestion->deleteComments($discussion["discussionID"], [0, 2]);
        }, $discussion["insertUserID"]);

        $this->assertSame(true, $removeStatus);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);

        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_UNANSWERED, $updatedDiscussion["statusID"]);
    }

    /**
     * Test generation of suggestions
     *
     * @dataProvider ConfigDataProvider
     */
    public function testGenerationOfSuggestionTurnedOffConfig(array $config, $exception = "")
    {
        if ($exception != "") {
            $this->expectExceptionMessage($exception);
        }
        $this->runWithConfig($config, function () {
            $discussion = $this->createDiscussion(["type" => "question"]);

            $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
            $suggestions = $newDiscussion["Attributes"]["suggestions"] ?? [];
            $this->assertCount(0, $suggestions);
        });
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
            "Provider turned off" => [
                [
                    "aiSuggestions" => [
                        "enabled" => true,
                        "sources" => ["mockSuggestion" => ["enabled" => false]],
                    ],
                ],
            ],
        ];
        return $result;
    }

    /**
     * Test dismissing suggestions
     *
     * @return array
     */
    public function testDismissSuggestions()
    {
        $discussion = $this->createDiscussion(["type" => "question"]);

        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];

        // Test that suggestions are not hidden by default
        $this->assertCount(3, $suggestions);
        $this->assertSame(false, $suggestions[0]["hidden"]);
        $this->assertSame(false, $suggestions[1]["hidden"]);
        $this->assertSame(false, $suggestions[2]["hidden"]);

        // Hide the first two suggestions.
        $this->suggestionSourceService->toggleSuggestions($newDiscussion, [0, 1]);
        $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $newDiscussion["Attributes"]["suggestions"];
        $this->assertSame(true, $suggestions[0]["hidden"]);
        $this->assertSame(true, $suggestions[1]["hidden"]);
        $this->assertSame(false, $suggestions[2]["hidden"]);
        return $newDiscussion;
    }

    /**
     * Test restoring suggestions
     *
     * @return void
     * @depends testDismissSuggestions
     */
    public function testRestoreSuggestions(array $discussion)
    {
        // Test that all suggestions are no longer hidden.
        $this->suggestionSourceService->toggleSuggestions($discussion, hide: false);
        $discussion = $this->discussionModel->getID($discussion["DiscussionID"], DATASET_TYPE_ARRAY);
        $suggestions = $discussion["Attributes"]["suggestions"];
        $this->assertSame(false, $suggestions[0]["hidden"]);
        $this->assertSame(false, $suggestions[1]["hidden"]);
        $this->assertSame(false, $suggestions[2]["hidden"]);
    }

    /**
     * Tests that notifications are sent after suggestions are generated.
     *
     * @return void
     */
    public function testNotificationsAfterSuggestionsGenerated()
    {
        $user = $this->createUser();
        $this->runWithUser(function () {
            $discussion = $this->createDiscussion(["type" => "question"]);
            $newDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
            $suggestions = $newDiscussion["Attributes"]["suggestions"];
            $this->assertCount(3, $suggestions);
        }, $user);

        $this->assertUserHasNotificationsLike($user, [
            new ExpectedNotification("Default", [$this->assistantUser["name"], "has suggested answers: check it out"]),
        ]);
        $this->assertUserHasNoEmails($user);
    }

    /**
     * Provides test coverage for suggestions notification preferences and checks that e-mail is not available.
     *
     * @return void
     */
    public function testSuggestionsNotificationPreferences()
    {
        $schema = $this->api()
            ->get("/notification-preferences/schema")
            ->getBody();

        $notificationPreferences = ArrayUtils::getByPath(
            "properties.notifications.properties.followedPosts.properties.AiSuggestions.properties",
            $schema
        );
        $this->assertArrayNotHasKey("email", $notificationPreferences);
        $this->assertArrayHasKey("popup", $notificationPreferences);
    }
}
