<?php
/**
 * @author Pavel Goncharov <pgoncharov@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace APIv2;

use Gdn;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test discussion splitting.
 */
class DiscussionsSplitTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["QnA", "ideation"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->enableFeature("customLayout.post");

        // These tests do not work with custom post types.
        $this->disableFeature(PostTypeModel::FEATURE_POST_TYPES);
        $this->userPreferenceModel = Gdn::getContainer()->get(UserNotificationPreferencesModel::class);
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES);
    }

    /**
     * Test success PATCH /discussions/split
     */
    public function testSuccessSplitDiscussion(): void
    {
        // Create our records.
        $this->resetCategoryTable();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->enableCaching();
        $rootCategory = $this->createCategory();
        CurrentTimeStamp::mockTime("2022-01-01");
        $category1 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);
        $discussion1 = $this->createDiscussion();
        $index = 0;
        $comments = [];
        while ($index < 30) {
            $comments[] = $this->createComment(["body" => "comment" . $index++]);
        }
        $category2 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);
        $commentIDsToSplit = array_column(array_slice($comments, 10, 10), "commentID");
        // Split the records.
        $response = $this->api()->post("/discussions/split", [
            "commentIDs" => $commentIDsToSplit,
            "newPost" => [
                "name" => "New Discussion",
                "body" => "Discussion",
                "format" => "html",
                "categoryID" => $category2["categoryID"],
                "postType" => "Discussion",
                "authorType" => "me",
            ],
        ]);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEqualsCanonicalizing([count($commentIDsToSplit)], $body["progress"]["successIDs"]);

        $result = $this->api()->get("/comments", ["commentID" => $comments[11]["commentID"]]);
        $this->assertCount(1, $result->getBody());
        $movedComment = $result->getBody()[0];
        $newDiscussionID = $movedComment["discussionID"];
        $this->assertNotSame($movedComment["discussionID"], $discussion1["discussionID"]);

        $result = $this->api()->get("/comments", ["commentID" => $comments[0]["commentID"]]);
        $this->assertCount(1, $result->getBody());
        $notMovedComment = $result->getBody()[0];
        $this->assertSame($notMovedComment["discussionID"], $discussion1["discussionID"]);

        // Our comments should have been moved.
        $movedComments = $this->api()->get("/comments", ["discussionID" => $newDiscussionID]);
        $this->assertRowsLike(
            [
                "body" => array_column(array_slice($comments, 10, 10), "body"),
            ],
            $movedComments->getBody(),
            false,
            10
        );

        // Make sure counts were adjusted properly.
        $categoryIDs = [$rootCategory["categoryID"], $category2["categoryID"], $category1["categoryID"]];
        $categories = $this->api()
            ->get("/categories", ["categoryID" => $categoryIDs])
            ->getBody();
        $this->assertRowsLike(
            [
                "categoryID" => $categoryIDs,
                "countComments" => [0, 10, 20],
                "countDiscussions" => [0, 1, 1],
            ],
            $categories,
            true,
            3
        );

        // Make sure the "lastPost" was adjusted appropriately on the category and the discussion.
        // New discussion should properly account for the
        $targetDiscussion = $this->api()->get("/discussions/{$newDiscussionID}", ["expand" => "lastPost"]);
        $this->assertArraySubsetRecursive(
            [
                "commentID" => $comments[19]["commentID"],
                "dateInserted" => $comments[19]["dateInserted"],
            ],
            $targetDiscussion["lastPost"]
        );

        // Make sure the category lastPost is correct.
        $category = $this->api()->get("/categories/{$category2["categoryID"]}", ["expand" => "lastPost"]);
        $this->assertArraySubsetRecursive(
            [
                "commentID" => $comments[19]["commentID"],
                "dateInserted" => $comments[19]["dateInserted"],
            ],
            $category["lastPost"]
        );
    }

    /**
     * Test success PATCH /discussions/split
     */
    public function testSuccessSplitNestedComments(): void
    {
        // Create our records.
        $this->resetCategoryTable();
        $this->resetTable("Discussion");
        $this->resetTable("Comment");
        $this->enableCaching();
        $rootCategory = $this->createCategory(["allowedDiscussionTypes" => ["Question"]]);
        CurrentTimeStamp::mockTime("2022-01-01");
        $roles = $this->getRoles();
        $category1 = $this->createPermissionedCategory(
            ["parentCategoryID" => $rootCategory["categoryID"]],
            [$roles["Member"]]
        );

        $memberUser = $this->createUser([
            "name" => "testNotications2",
        ]);

        $this->userPreferenceModel->save($memberUser["userID"], [
            "Popup.NewDiscussion.{$category1["categoryID"]}" => 1,
            "Popup.NewComment.{$category1["categoryID"]}" => 1,
        ]);

        $discussion1 = $this->createDiscussion();
        // Create some nested comments
        $comment0 = $this->createComment(["body" => "test Comment0"]);
        $comment1 = $this->createComment(["body" => "test Comment1"]);
        $comment1_1 = $this->createNestedComment($comment1, ["body" => "test Comment11"]);
        $comment1_2 = $this->createNestedComment($comment1, ["body" => "test Comment12"]);
        $comment1_2_1 = $this->createNestedComment($comment1_2, ["body" => "test Comment121"]);

        $comment2 = $this->createComment(["body" => "test Comment2"]);
        $comment2_1 = $this->createNestedComment($comment2, ["body" => "test Comment21"]);
        $comment2_2 = $this->createNestedComment($comment2, ["body" => "test Comment22"]);
        $comment2_2_1 = $this->createNestedComment($comment2_2, ["body" => "test Comment221"]);
        $comment2_3 = $this->createNestedComment($comment2, ["body" => "test Comment23"]);
        $comment2_3_1 = $this->createNestedComment($comment2_3, ["body" => "test Comment231"]);

        $this->api()->setUserID($memberUser["userID"]);
        $oldNotifications = $this->api()
            ->get("/notifications")
            ->getBody();
        // Make sure we get some new discussion notification.
        $this->assertNotCount(0, $oldNotifications);
        $this->api()->setUserID($discussion1["insertUserID"]);

        $commentsToSplit = [$comment0, $comment1, $comment1_2_1, $comment2_1, $comment2_2];
        $commentsExpectedToSplit = [$comment0, $comment1, $comment1_1, $comment1_2, $comment1_2_1];
        //$comment2_2 comment split but moved to root of the nested comments.
        $category2 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);

        $commentIDsToSplit = array_column($commentsToSplit, "commentID");
        // Split the records.
        $response = $this->api()->post("/discussions/split", [
            "commentIDs" => $commentIDsToSplit,
            "newPost" => [
                "name" => "New Discussion",
                "body" => "Discussion",
                "format" => "html",
                "categoryID" => $category2["categoryID"],
                "postType" => "Question",
                "authorType" => "me",
            ],
        ]);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEqualsCanonicalizing(
            [$commentIDsToSplit[1], $commentIDsToSplit[4], 3],
            $body["progress"]["successIDs"]
        );

        $this->api()->setUserID($memberUser["userID"]);

        // No new Notifications should have been created.
        $notifications = $this->api()
            ->get("/notifications")
            ->getBody();
        $this->assertCount(count($oldNotifications), $notifications);

        // Check directly selected comment.
        foreach ($commentsExpectedToSplit as $splitComment) {
            $result = $this->api()->get("/comments", ["commentID" => $splitComment["commentID"]]);
            $this->assertCount(1, $result->getBody());
            $movedComment = $result->getBody()[0];
            $this->assertNotSame($movedComment["discussionID"], $discussion1["discussionID"]);
            $this->assertSame($movedComment["depth"], $splitComment["depth"]);
        }

        //Comment split and moved out of the nested depth
        $result = $this->api()->get("/comments", [
            "commentID" => [$comment2_1["commentID"], $comment2_2["commentID"], $comment2_2_1["commentID"]],
        ]);
        $this->assertCount(3, $result->getBody());
        $movedComments = $result->getBody();
        $newDiscussionID = $movedComments[0]["discussionID"];
        $this->assertNotSame($movedComments[0]["discussionID"], $discussion1["discussionID"]);
        $this->assertSame($movedComments[0]["depth"], 1);
        $this->assertSame($movedComments[1]["depth"], 1);
        $this->assertSame($movedComments[2]["depth"], 2);

        //,

        //not moved comment
        $result = $this->api()->get("/comments", ["commentID" => $comment2_3_1["commentID"]]);
        $this->assertCount(1, $result->getBody());
        $notMovedComment = $result->getBody()[0];
        $this->assertSame($notMovedComment["discussionID"], $comment2_3_1["discussionID"]);

        // Make sure counts were adjusted properly.
        $categoryIDs = [$rootCategory["categoryID"], $category2["categoryID"], $category1["categoryID"]];
        $categories = $this->api()
            ->get("/categories", ["categoryID" => $categoryIDs])
            ->getBody();
        $this->assertRowsLike(
            [
                "categoryID" => $categoryIDs,
                "countComments" => [0, 8, 3],
                "countDiscussions" => [0, 1, 1],
            ],
            $categories,
            true,
            3
        );

        // Make sure the "lastPost" was adjusted appropriately on the category and the discussion.
        // New discussion should properly account for the
        $targetDiscussion = $this->api()->get("/discussions/{$newDiscussionID}", ["expand" => "lastPost"]);
        $this->assertArraySubsetRecursive(
            [
                "commentID" => $comment2_2_1["commentID"],
                "dateInserted" => $comment2_2_1["dateInserted"],
            ],
            $targetDiscussion["lastPost"]
        );

        // Make sure the category lastPost is correct.
        $category = $this->api()->get("/categories/{$category2["categoryID"]}", ["expand" => "lastPost"]);
        $this->assertArraySubsetRecursive(
            [
                "commentID" => $comment2_2_1["commentID"],
                "dateInserted" => $comment2_2_1["dateInserted"],
            ],
            $category["lastPost"]
        );
    }

    /**
     * Test a user trying to split with missing permissions, or comments from multiple discussions.
     */
    public function testInvalidPermissions()
    {
        $memberID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $rootCategory = $this->createCategory();
        $categoryID = $this->lastInsertedCategoryID;
        $permSource = $this->createDiscussion();
        $permComments[] = $this->createComment(["body" => "comment1"])["commentID"];
        $permComments[] = $this->createComment(["body" => "comment2"])["commentID"];

        $noPermCategory = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID]);
        $noPermSource = $this->createDiscussion();
        $noPermComments[] = $this->createComment(["body" => "comment3"])["commentID"];
        $noPermComments[] = $this->createComment(["body" => "comment4"])["commentID"];

        $this->runWithUser(function () use ($categoryID, $permComments, $noPermComments, $noPermCategory) {
            $this->runWithExpectedExceptionCode(403, function () use ($categoryID, $permComments) {
                $this->api()->post("/discussions/split", [
                    "commentIDs" => $permComments,
                    "newPost" => [
                        "name" => "New Discussion",
                        "body" => "Discussion",
                        "format" => "html",
                        "categoryID" => $categoryID,
                        "postType" => "Discussion",
                        "authorType" => "me",
                    ],
                ]);
            });

            $this->runWithExpectedExceptionCode(404, function () use ($noPermComments, $noPermCategory) {
                $this->api()->post("/discussions/split", [
                    "commentIDs" => $noPermComments,
                    "newPost" => [
                        "name" => "New Discussion",
                        "body" => "Discussion",
                        "format" => "html",
                        "categoryID" => $noPermCategory["categoryID"],
                        "postType" => "Discussion",
                        "authorType" => "me",
                    ],
                ]);
            });

            $this->runWithExpectedExceptionCode(404, function () use ($noPermComments, $categoryID) {
                $this->api()->post("/discussions/split", [
                    "commentIDs" => $noPermComments,
                    "newPost" => [
                        "name" => "New Discussion",
                        "body" => "Discussion",
                        "format" => "html",
                        "categoryID" => $categoryID,
                        "postType" => "Discussion",
                        "authorType" => "me",
                    ],
                ]);
            });
        }, $memberID);

        $this->runWithExpectedExceptionCode(400, function () use ($noPermComments, $permComments, $categoryID) {
            $this->api()->post("/discussions/split", [
                "commentIDs" => array_merge($permComments, $noPermComments),
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => $categoryID,
                    "postType" => "Discussion",
                    "authorType" => "me",
                ],
            ]);
        });
    }

    /**
     * Check that a not found error is returned if we try to split non-existing discussions.
     */
    public function testNotFound()
    {
        $this->createDiscussion();
        $this->runWithExpectedExceptionCode(404, function () {
            $this->api()->post("/discussions/split", [
                "commentIDs" => [500, 5000, 50000],
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => 1,
                    "postType" => "Discussion",
                    "authorType" => "me",
                ],
            ]);
        });
    }

    /**
     * Check that a not found error is returned if we try to split non-existing discussions.
     */
    public function testInvalidPostType()
    {
        $this->createDiscussion();
        $comments[] = $this->createComment();
        $comments[] = $this->createComment();
        $comments[] = $this->createComment();

        $category = $this->createCategory([
            "allowedDiscussionTypes" => ["Discussion", "Question"],
            "customPermissions" => true,
        ]);
        $this->runWithExpectedExceptionCode(400, function () use ($category, $comments) {
            $this->api()->post("/discussions/split", [
                "commentIDs" => array_column($comments, "commentID"),
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => $category["categoryID"],
                    "postType" => "Idea",
                    "authorType" => "me",
                ],
            ]);
        });

        $ideaCategory = $this->createCategory([
            "ideationType" => "up",
            "allowedDiscussionTypes" => ["Idea"],
            "customPermissions" => true,
        ]);
        $this->runWithExpectedExceptionCode(400, function () use ($ideaCategory, $comments) {
            $this->api()->post("/discussions/split", [
                "commentIDs" => array_column($comments, "commentID"),
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => $ideaCategory["categoryID"],
                    "postType" => "Discussion",
                    "authorType" => "me",
                ],
            ]);
        });
    }

    /**
     * Test that we can partially complete a merge.
     *
     * @return mixed
     */
    public function testSplitPartial()
    {
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
        $this->getLongRunner()->setMaxIterations(1);
        // Create our records.
        $this->resetCategoryTable();
        $this->enableCaching();
        $rootCategory = $this->createCategory();
        $category1 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);
        $discussion1 = $this->createDiscussion();

        // Create some nested comments
        $comment0 = $this->createComment(["body" => "test Comment0"]);
        $comment1 = $this->createComment(["body" => "test Comment1"]);
        $comment1_1 = $this->createNestedComment($comment1, ["body" => "test Comment11"]);
        $comment1_2 = $this->createNestedComment($comment1, ["body" => "test Comment12"]);
        $comment1_2_1 = $this->createNestedComment($comment1_2, ["body" => "test Comment121"]);

        $comment2 = $this->createComment(["body" => "test Comment2"]);
        $comment2_1 = $this->createNestedComment($comment2, ["body" => "test Comment21"]);
        $comment2_2 = $this->createNestedComment($comment2, ["body" => "test Comment22"]);
        $comment2_2_1 = $this->createNestedComment($comment2_2, ["body" => "test Comment221"]);
        $comment2_3 = $this->createNestedComment($comment2, ["body" => "test Comment23"]);
        $comment2_3_1 = $this->createNestedComment($comment2_3, ["body" => "test Comment231"]);

        $commentsToSplit = [$comment0, $comment1, $comment1_2_1, $comment2_1, $comment2_2];
        $commentsExpectedToSplit = [
            $comment0,
            $comment1,
            $comment1_1,
            $comment1_2,
            $comment1_2_1,
            $comment2_2,
            $comment2_2_1,
        ];
        //$comment2_2 comment split but moved to root of the nested comments.
        $category2 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);

        $commentIDsToSplit = array_column($commentsToSplit, "commentID");
        $response = $this->api()->post(
            "/discussions/split",
            [
                "commentIDs" => $commentIDsToSplit,
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => $category2["categoryID"],
                    "postType" => "Discussion",
                    "authorType" => "me",
                ],
            ],
            [],
            ["throw" => false]
        );

        $this->assertEquals(408, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(5, $body["progress"]["countTotalIDs"]);
        $this->assertCount(1, $body["progress"]["successIDs"]);
        $this->assertNotNull($body["callbackPayload"]);
        return $body["callbackPayload"];
    }

    /**
     * Test that we can resume and finish our split.
     *
     * @param string $callbackPayload
     *
     * @depends testSplitPartial
     */
    public function testResumePartial(string $callbackPayload)
    {
        $this->getLongRunner()->reset();
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode(), "Long runner should complete. " . $response->getRawBody());
        $this->assertEquals(5, $response->getBody()["progress"]["countTotalIDs"]);
        $allDiscussions = $this->api()
            ->get("/discussions")
            ->getBody();
        $this->assertCount(2, $allDiscussions);
        $this->assertEquals(8, $allDiscussions[1]["countComments"]);
        $this->assertEquals(3, $allDiscussions[0]["countComments"]);
    }

    /**
     * Test that the split comment workflow works with the postTypes.
     *
     * @return void
     */
    public function testSplitDiscussionWithPostType(): void
    {
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES);
        $this->createCategory();
        $this->createDiscussion();
        $comment = $this->createComment();

        $postType = $this->createPostType([
            "name" => "Test Post Type",
            "description" => "Test Post Type",
            "icon" => "test",
        ]);

        $this->api()
            ->post("/discussions/split", [
                "commentIDs" => [$comment["commentID"]],
                "newPost" => [
                    "name" => "New Discussion",
                    "body" => "Discussion",
                    "format" => "html",
                    "categoryID" => $this->lastInsertedCategoryID,
                    "postType" => $postType["postTypeID"],
                    "authorType" => "me",
                ],
            ])
            ->assertSuccess();

        $updatedComment = $this->api()
            ->get("comments", ["commentID" => $comment["commentID"]])
            ->getBody();
        $this->assertNotSame($comment["discussionID"], $updatedComment[0]["discussionID"]);

        $this->api()
            ->get("/discussions/{$updatedComment[0]["discussionID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike([
                "postTypeID" => $postType["postTypeID"],
                "type" => $postType["parentPostTypeID"],
            ]);
    }
}
