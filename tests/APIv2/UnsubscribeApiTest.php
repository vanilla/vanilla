<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ActivityModel;
use CategoryModel;
use Ramsey\Uuid\Uuid;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UnsubscribeActivityTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/unsubscribe endpoints.
 */
class UnsubscribeApiTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    use UnsubscribeActivityTrait;

    /** @var ActivityModel */
    protected $activityModel;

    /** @var ActivityService */
    protected $activityService;

    /** @var UserNotificationPreferencesModel */
    protected $userNotificationPreferencesModel;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = "/unsubscribe";
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->userNotificationPreferencesModel = $this->container()->get(UserNotificationPreferencesModel::class);
        $this->config = $this->container()->get(ConfigurationInterface::class);
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
        $this->assertEquals(
            [["preference" => "Email.DiscussionComment", "enabled" => false, "userID" => $notifyUserID]],
            $body["preferences"]
        );
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $this->assertEquals("0", $discussionComment);

        //Oops Resubscribe, didn't meant it.
        $response = $this->api()->post("{$this->baseUrl}/resubscribe/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [["preference" => "Email.DiscussionComment", "enabled" => true, "userID" => $notifyUserID]],
            $body["preferences"]
        );
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $this->assertEquals("1", $discussionComment);
    }

    /**
     * Test unsubscribe Digest link and token and resubscribe.
     *
     * @return void
     */
    public function testUnsubscribeDigestResubscribeLinkToken()
    {
        $notifyUserID = 2;

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DigestEnabled" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeDigestLink($notifyUser);

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        // Unsubscribe 1 preference
        $response = $this->api()->post("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [["preference" => "Email.DigestEnabled", "enabled" => false, "userID" => $notifyUserID]],
            $body["preferences"]
        );
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DigestEnabled", $notifyUser, null);
        $this->assertEquals("0", $discussionComment);

        //Oops Resubscribe, didn't meant it.
        $response = $this->api()->post("{$this->baseUrl}/resubscribe/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [["preference" => "Email.DigestEnabled", "enabled" => true, "userID" => $notifyUserID]],
            $body["preferences"]
        );
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DigestEnabled", $notifyUser, null);
        $this->assertEquals("1", $discussionComment);
    }

    /**
     * Test unsubscribe category follow Digest link and token and resubscribe.
     *
     * @return void
     */
    public function testUnsubscribeCategoryDigestResubscribeLinkToken()
    {
        $notifyUserID = 2;
        $category = $this->createCategory();
        \Gdn::config()->set("Garden.Digest.Enabled", true);
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $result = $this->api()->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DIGEST => true,
            ]);
            $this->assertArrayHasKey(\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST, $result);
            $this->assertTrue($result[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST]);
        }, $notifyUserID);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DigestEnabled" => "1",
        ]);
        $unsubscribeLink = $this->activityModel->getUnfollowCategoryLink($notifyUser, $category["categoryID"]);

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        // Unsubscribe 1 preference
        $response = $this->api()->post("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [
                "preference" => "Preferences.Email.Digest.{$category["categoryID"]}",
                "name" => $category["name"],
                "enabled" => "0",
                "userID" => $notifyUserID,
                "categoryID" => $category["categoryID"],
            ],
            $body["followCategory"]
        );
        $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
            $notifyUserID,
            "Preferences.Email.Digest.{$category["categoryID"]}",
            [],
            "Preferences."
        );
        $discussionComment = val("Email.Digest.{$category["categoryID"]}", $metaPrefs, null);
        $this->assertEquals("0", $discussionComment);

        //Oops Resubscribe, didn't meant it.
        $response = $this->api()->post("{$this->baseUrl}/resubscribe/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [
                "preference" => "Preferences.Email.Digest.{$category["categoryID"]}",
                "name" => $category["name"],
                "enabled" => "1",
                "userID" => $notifyUserID,
                "categoryID" => $category["categoryID"],
            ],
            $body["followCategory"]
        );
        $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
            $notifyUserID,
            "Preferences.Email.Digest.%",
            [],
            "Preferences."
        );
        $discussionComment = val("Email.Digest.{$category["categoryID"]}", $metaPrefs, null);
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
                ["preference" => "Email.DiscussionComment", "enabled" => true, "userID" => $notifyUserID],
                ["preference" => "Email.BookmarkComment", "enabled" => true, "userID" => $notifyUserID],
            ],
            $body["preferences"]
        );
        $response = $this->api()->patch("{$this->baseUrl}/$token}", [
            "preferences" => [["preference" => "Email.DiscussionComment", "enabled" => "0", "userID" => $notifyUserID]],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $discussionComment = val("Email.DiscussionComment", $notifyUser, null);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser, null);
        $this->assertEquals("0", $discussionComment);
        $this->assertEquals("1", $bookmarkComment);
    }

    /**
     * Test unsubscribe link and token with bookmark and follow category(unsubscribe).
     *
     * @return void
     */
    public function testUnsubscribeFollowingLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $category = $this->createCategory();
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $this->api()->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
            ]);
        }, $notifyUserID);
        $activity = $this->activityModel->save([
            "ActivityType" => "Comment",
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => [
                "Name" => "Discussion that I follow 2",
                "Category" => $category["name"],
                "Reason" => "bookmark, participated, advanced",
            ],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
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
                "preferences" => [
                    ["preference" => "Email.BookmarkComment", "enabled" => true, "userID" => $notifyUserID],
                ],
                "followCategory" => [
                    "categoryID" => $category["categoryID"],
                    "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                    "name" => $category["name"],
                    "enabled" => "1",
                    "userID" => $notifyUserID,
                ],
            ],
            $body
        );
        $response = $this->api()->patch("{$this->baseUrl}/$token}", [
            "followCategory" => [
                "categoryID" => $category["categoryID"],
                "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                "name" => $category["name"],
                "enabled" => "0",
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $following = $this->api()->get($url);
            $this->assertEquals(
                [
                    "preferences.followed" => true,
                    "preferences.popup.posts" => true,
                    "preferences.email.posts" => true,
                    "preferences.popup.comments" => true,
                    "preferences.email.comments" => false,
                    "preferences.email.digest" => false,
                ],
                $following->getBody()
            );
        }, $notifyUserID);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser, null);
        $this->assertEquals("1", $bookmarkComment);
    }

    /**
     * Test unsubscribe link and token with bookmark(unsubscribe) and follow category.
     *
     * @return void
     */
    public function testUnsubscribeBookmarkLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $category = $this->createCategory();
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $this->api()->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
            ]);
        }, $notifyUserID);
        $activity = $this->activityModel->save([
            "ActivityType" => "Comment",
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => [
                "Name" => "Discussion that I follow 2",
                "Category" => $category["name"],
                "Reason" => "bookmark, participated, advanced",
            ],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
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
                "preferences" => [
                    ["preference" => "Email.BookmarkComment", "enabled" => true, "userID" => $notifyUserID],
                ],
                "followCategory" => [
                    "categoryID" => $category["categoryID"],
                    "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                    "name" => $category["name"],
                    "enabled" => "1",
                    "userID" => $notifyUserID,
                ],
            ],
            $body
        );
        $response = $this->api()->patch("{$this->baseUrl}/$token}", [
            "preferences" => [["preference" => "Email.BookmarkComment", "enabled" => "0", "userID" => $notifyUserID]],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $following = $this->api()->get($url);
            $this->assertEquals(
                [
                    "preferences.followed" => true,
                    "preferences.popup.posts" => true,
                    "preferences.email.posts" => true,
                    "preferences.popup.comments" => true,
                    "preferences.email.comments" => true,
                    "preferences.email.digest" => false,
                ],
                $following->getBody()
            );
        }, $notifyUserID);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser, null);
        $this->assertEquals("0", $bookmarkComment);
    }

    /**
     * Test unsubscribe link and token with bookmark(unsubscribe) and follow category.
     *
     * @return void
     */
    public function testUnsubscribeManyReasonLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $category = $this->createCategory();
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $this->api()->patch($url, [
                \CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP => true,
                \CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL => true,
            ]);
        }, $notifyUserID);
        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        \Gdn::userMetaModel()->setUserMeta($notifyUser["UserID"], "Preferences.Email.BookmarkComment", "1");
        \Gdn::userMetaModel()->setUserMeta($notifyUser["UserID"], "Preferences.Email.Mention", "1");
        \Gdn::userMetaModel()->setUserMeta($notifyUser["UserID"], "Preferences.Email.DiscussionComment", "1");
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.BookmarkComment" => "1",
            "Email.Mention" => "1",
            "Email.DiscussionComment" => "1",
        ]);
        $activityObj = [
            "ActivityType" => "Comment",
            "ActivityUserID" => $activityUserID,
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => [
                "Name" => "Discussion that I follow 2",
                "Category" => $category["name"],
                "Reason" => "bookmark, participated, advanced",
            ],
        ];
        $activity = $this->activityModel->save($activityObj);
        $activityObj["Data"]["Reason"] = "Mention";
        $activityObj["Data"]["Preference"] = "DiscussionComment";
        $activity2 = $this->activityModel->save($activityObj);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activity["ActivityID"], $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $response = $this->api()->post("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            [
                "preferences" => [
                    ["preference" => "Email.Mention", "enabled" => "1", "userID" => $notifyUserID],
                    ["preference" => "Email.DiscussionComment", "enabled" => "1", "userID" => $notifyUserID],
                    ["preference" => "Email.BookmarkComment", "enabled" => "1", "userID" => $notifyUserID],
                ],
                "followCategory" => [
                    "categoryID" => $category["categoryID"],
                    "preference" => "Preferences.Email.NewComment.{$category["categoryID"]}",
                    "name" => $category["name"],
                    "enabled" => "1",
                    "userID" => $notifyUserID,
                ],
            ],
            $body
        );
        $response = $this->api()->patch("{$this->baseUrl}/$token}", [
            "preferences" => [
                ["preference" => "Email.BookmarkComment", "enabled" => "0", "userID" => $notifyUserID],
                ["preference" => "Email.Mention", "enabled" => "0", "userID" => $notifyUserID],
                ["preference" => "Email.DiscussionComment", "enabled" => "0", "userID" => $notifyUserID],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userNotificationPreferencesModel->getUserPrefs($notifyUserID);
        $this->runWithUser(function () use ($category, $notifyUserID) {
            $url = "/categories/{$category["categoryID"]}/preferences/{$notifyUserID}";
            $following = $this->api()->get($url);
            $this->assertEquals(
                [
                    "preferences.followed" => true,
                    "preferences.popup.posts" => true,
                    "preferences.email.posts" => true,
                    "preferences.popup.comments" => true,
                    "preferences.email.comments" => true,
                    "preferences.email.digest" => false,
                ],
                $following->getBody()
            );
        }, $notifyUserID);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser, null);
        $this->assertEquals("0", $bookmarkComment);
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
                        "userID" => $followUser["userID"],
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
                        "userID" => $followUser["userID"],
                    ],
                    $body["followCategory"]
                );
                $metaPrefs = \Gdn::userMetaModel()->getUserMeta(
                    $followUser["userID"],
                    "Preferences.Email.%",
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
                        "userID" => $followUser["userID"],
                    ],
                    $body["followCategory"]
                );
                $this->assertEquals(
                    [
                        "preference" => "Email.DiscussionComment",
                        "enabled" => "1",
                        "userID" => $followUser["userID"],
                    ],
                    $body["preferences"][0]
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
                    "Preferences.Email.%",
                    [],
                    "Preferences."
                );

                $followComment = val("Email.NewComment.{$category["categoryID"]}", $metaPrefs, 0);
                $this->assertEquals("0", $followComment);
            }
        );
    }

    /**
     * Test to make sure every activity type can be unsubscribed from.
     *
     * @return void
     */
    public function testUnsubscribeActivity(): void
    {
        $activitiesNames = $this->activityService->getActivities();
        $activityUser = $this->createUser();
        $notifyUser = $this->createUser();

        foreach ($activitiesNames as $activityName) {
            $this->unsubscribeActivityTest($activityName, $activityUser["userID"], $notifyUser["userID"]);
        }
    }
}
