<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ActivityModel;
use CategoryModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/roles endpoints.
 */
class UnsubscribeApiTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var ActivityModel */
    private $activityModel;

    /** @var UserNotificationPreferencesModel */
    private $userNotificationPreferencesModel;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = "/unsubscribe";
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->userNotificationPreferencesModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $this->config->set(["Feature.UnsubscribeLink.Enabled" => true], null);
    }

    /**
     * Test unsubscribe link and token and resubscribe.
     *
     * @return void
     */
    public function testUnsubscribeResubscribeLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $activity = $this->activityModel->save([
            "ActivityType" => "DiscussionComment",
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "DiscussionComment"],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DiscussionComment" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activity["ActivityID"], $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        // Unsubscribe 1 preference
        $response = $this->api()->post("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([["preference" => "Email.DiscussionComment", "enabled" => false]], $body["preferences"]);
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $this->assertEquals("0", $discussionComment);

        //Oops Resubscribe, didn't meant it.
        $response = $this->api()->post("{$this->baseUrl}/resubscribe/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([["preference" => "Email.DiscussionComment", "enabled" => true]], $body["preferences"]);
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $this->assertEquals("1", $discussionComment);
    }

    /**
     * Test unsubscribe link and token.
     *
     * @return void
     */
    public function testUnsubscribeLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $activity = $this->activityModel->save([
            "ActivityType" => "DiscussionComment",
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "DiscussionComment, BookmarkComment"],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DiscussionComment" => "1",
            "Email.BookmarkComment" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activity["ActivityID"], $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $response = $this->api()->post("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [
                ["preference" => "Email.DiscussionComment", "enabled" => true],
                ["preference" => "Email.BookmarkComment", "enabled" => true],
            ],
            $body["preferences"]
        );
        $response = $this->api()->patch("{$this->baseUrl}/$token}", [
            "preferences" => [["preference" => "Email.DiscussionComment", "enabled" => "0"]],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser, null);
        $this->assertEquals("0", $discussionComment);
        $this->assertEquals("1", $bookmarkComment);
    }

    /**
     * Test unsubscribe disabled, returns Not Found.
     *
     * @return void
     */
    public function testUnsubscribeNotEnabled()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $activity = $this->activityModel->save([
            "ActivityType" => "DiscussionComment",
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "DiscussionComment, BookmarkComment"],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DiscussionComment" => "1",
            "Email.BookmarkComment" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activity["ActivityID"], $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $this->runWithConfig(["Feature.UnsubscribeLink.Enabled" => false], function () use ($token) {
            $this->expectExceptionMessage("Page not found.");
            $this->api()->post("{$this->baseUrl}/$token}");
        });
    }

    /**
     * Test that notifications get sent for items in categories more than 2 deep. and can be unsubscribed
     */
    public function testUnsubscribeCategoryFollow()
    {
        $this->runWithConfig(
            [
                \CategoryModel::CONF_CATEGORY_FOLLOWING => true,
            ],
            function () {
                // Create categories.
                $this->createCategory();
                $category = $this->createCategory();
                $this->assertNotEquals(CategoryModel::ROOT_ID, $category["parentCategoryID"]);

                // Create Users
                $postUser = $this->createUser();
                $followUser = $this->createUser();

                // Follow the category.
                $this->runWithUser(function () use ($category, $followUser) {
                    $url = "/categories/{$category["categoryID"]}/preferences/{$followUser["userID"]}";
                    $this->api()->patch($url, [
                        \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
                    ]);
                }, $followUser);
                // Create the posts
                $this->runWithUser(function () use ($category) {
                    $discussion = $this->createDiscussion(["name" => "Hello Discussion"]);
                    $this->assertEquals($category["categoryID"], $discussion["categoryID"]);
                    $this->createComment();
                }, $postUser);
                // Follow user should have 2 app notifications and 2 email notifications.
                $this->runWithUser(function () use ($followUser) {
                    $this->assertEquals(2, $this->activityModel->getUserTotalUnread($followUser["userID"]));
                }, $followUser);
                $activities = $this->activityModel->getByUser($followUser["userID"])->resultArray();
                $notifyUser = $this->userModel->getID($followUser["userID"], DATASET_TYPE_ARRAY);
                $unsubscribeLink = $this->activityModel->getUnsubscribeLink(
                    $activities[0]["ActivityID"],
                    $notifyUser,
                    "text"
                );

                $link = explode("/unsubscribe/", $unsubscribeLink);
                $token = $link[1];
                $response = $this->api()->post("{$this->baseUrl}/$token}");
                $body = $response->getBody();
                $this->assertEquals(201, $response->getStatusCode());
                $this->assertEquals(
                    [
                        "categoryID" => $category["categoryID"],
                        "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                        "name" => $category["name"],
                        "enabled" => "0",
                    ],
                    $body["followCategory"]
                );
                $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
                    $followUser["userID"],
                    "Preferences.%",
                    [],
                    "Preferences."
                );

                $followComment = val("Email.NewComment.{$category["categoryID"]}", $metaPrefs, 0);
                $this->assertEquals("0", $followComment);

                //Oops Resubscribe, didn't meant it.
                $response = $this->api()->post("{$this->baseUrl}/resubscribe/$token}");
                $body = $response->getBody();
                $this->assertEquals(201, $response->getStatusCode());
                $this->assertEquals(
                    [
                        "categoryID" => $category["categoryID"],
                        "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                        "name" => $category["name"],
                        "enabled" => "1",
                    ],
                    $body["followCategory"]
                );
                $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
                    $followUser["userID"],
                    "Preferences.%",
                    [],
                    "Preferences."
                );

                $followComment = val("Email.NewComment.{$category["categoryID"]}", $metaPrefs, 0);
                $this->assertEquals("1", $followComment);
            }
        );
    }

    /**
     * Test that notifications get sent for items in categories more than 2 deep. and can be unsubscribed
     */
    public function testUnsubscribeCategoryFollowWithPreferences()
    {
        $this->runWithConfig(
            [
                \CategoryModel::CONF_CATEGORY_FOLLOWING => true,
            ],
            function () {
                // Create categories.
                $this->createCategory();
                $category = $this->createCategory();
                $this->assertNotEquals(CategoryModel::ROOT_ID, $category["parentCategoryID"]);

                // Create Users
                $postUser = $this->createUser();
                $followUser = $this->createUser();
                $this->userModel->savePreference($followUser["userID"], [
                    "Email.NewDiscussion" => "1",
                    "Email.NewComment" => "1",
                    "Email.DiscussionComment" => "1",
                ]);
                // Follow the category.
                $this->runWithUser(function () use ($category, $followUser) {
                    $url = "/categories/{$category["categoryID"]}/preferences/{$followUser["userID"]}";
                    $this->api()->patch($url, [
                        \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                        \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
                    ]);
                }, $followUser);
                // Create the posts
                $this->runWithUser(function () use ($category) {
                    $discussion = $this->createDiscussion(["name" => "Hello Discussion"]);
                }, $followUser);
                $this->runWithUser(function () use ($category) {
                    $this->createComment();
                }, $postUser);
                $activities = $this->activityModel->getByUser($followUser["userID"])->resultArray();
                $notifyUser = $this->userModel->getID($followUser["userID"], DATASET_TYPE_ARRAY);
                $unsubscribeLink = $this->activityModel->getUnsubscribeLink(
                    $activities[count($activities) - 1]["ActivityID"],
                    $notifyUser,
                    "text"
                );

                $link = explode("/unsubscribe/", $unsubscribeLink);
                $token = $link[1];
                $response = $this->api()->post("{$this->baseUrl}/$token}");
                $body = $response->getBody();
                $this->assertEquals(201, $response->getStatusCode());
                $this->assertEquals(
                    [
                        "categoryID" => $category["categoryID"],
                        "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                        "name" => $category["name"],
                        "enabled" => "1",
                    ],
                    $body["followCategory"]
                );
                $this->assertEquals(
                    [
                        [
                            "preference" => "Email.DiscussionComment",
                            "enabled" => "1",
                        ],
                    ],
                    $body["preferences"]
                );
                $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
                    $followUser["userID"],
                    "Preferences.%",
                    [],
                    "Preferences."
                );
                $followComment = val("Email.NewComment.{$category["categoryID"]}", $metaPrefs, 0);
                $this->assertEquals("1", $followComment);

                // unset both category following and DiscussionComment preference.
                $body["followCategory"]["enabled"] = "0";
                $body["preferences"] = [["preference" => "Email.DiscussionComment", "enabled" => "0"]];
                $response = $this->api()->patch("{$this->baseUrl}/$token}", $body);
                $this->assertEquals(200, $response->getStatusCode());

                $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($followUser["userID"]);
                $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
                $this->assertEquals("0", $discussionComment);

                $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
                    $followUser["userID"],
                    "Preferences.%",
                    [],
                    "Preferences."
                );

                $followComment = val("Email.NewComment.{$category["categoryID"]}", $metaPrefs, 0);
                $this->assertEquals("0", $followComment);
            }
        );
    }
}
