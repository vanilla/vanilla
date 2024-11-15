<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use CommentModel;
use DiscussionModel;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Models\DirtyRecordModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/comments endpoints.
 */
class CommentsTest extends AbstractResourceTest
{
    public static $addons = ["stubcontent", "test-mock-issue"];

    use TestExpandTrait;
    use AssertLoggingTrait;
    use TestPrimaryKeyRangeFilterTrait;
    use TestSortingTrait;
    use TestFilterDirtyRecordsTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/comments";
        $this->resourceName = "comment";
        $this->record += ["discussionID" => 1];
        $this->sortFields = ["dateInserted", "commentID"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @inheritdoc
     */
    protected function getExpandableUserFields()
    {
        return ["insertUser"];
    }

    /**
     * @inheritdoc
     */
    public function indexUrl()
    {
        $indexUrl = $this->baseUrl;
        $indexUrl .= "?" . http_build_query(["discussionID" => 1]);
        return $indexUrl;
    }

    /**
     * Verify that custom category permissions don't wipe out access to all comments.
     */
    public function testCustomCategoryPermissions()
    {
        // Default discussion ID. This is created during install.
        $discussionID = 1;

        // Create a new user for this test. It will receive the default member role.
        $username = substr(__FUNCTION__, 0, 20);
        $user = $this->api()
            ->post("users", [
                "name" => $username,
                "email" => $username . "@example.com",
                "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            ])
            ->getBody();
        $this->assertCount(1, $user["roles"], "User has too many default roles.");
        $roleID = $user["roles"][0]["roleID"];

        // Switch to the user we just created and comment on the default discussion.
        $this->api()->setUserID($user["userID"]);
        $this->api()->post("comments", [
            "body" => "Hello world.",
            "format" => "text",
            "discussionID" => $discussionID,
        ]);
        $comments = $this->api()
            ->get("comments", [
                "discussionID" => $discussionID,
            ])
            ->getBody();

        // Switch back to the admin user and add a new category.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $category = $this->api()
            ->post("categories", [
                "name" => __FUNCTION__,
                "urlcode" => strtolower(__FUNCTION__),
            ])
            ->getBody();

        // Update the permissions of the default member role to revoke permissions to the new category.
        $this->api()->patch("roles/{$roleID}/permissions", [
            [
                "id" => $category["categoryID"],
                "type" => "category",
                "permissions" => [
                    "comments.add" => false,
                    "comments.delete" => false,
                    "comments.edit" => false,
                    "discussions.add" => false,
                    "discussions.manage" => false,
                    "discussions.moderate" => false,
                    "discussions.view" => false,
                ],
            ],
        ]);

        // Switch back to the user we created and make sure they can still see the same comments as before.
        $this->api()->setUserID($user["userID"]);
        DiscussionModel::categoryPermissions(false, true);
        $updatedComments = $this->api()
            ->get("comments", [
                "discussionID" => $discussionID,
            ])
            ->getBody();

        $this->assertEquals($comments, $updatedComments);
    }

    /**
     * Verify that a user cannot delete their own comment via the API.
     */
    public function testDeletingOwnCommentNotAllowed()
    {
        $userID = $this->createUserFixture(self::ROLE_MEMBER);
        $this->api()->setUserID($userID);
        $comment = $this->api()
            ->post("comments", [
                "body" => 'You can\'t delete this',
                "format" => "text",
                "discussionID" => 1,
            ])
            ->getBody();

        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("Permission Problem");

        $this->api()->delete("comments/{$comment["commentID"]}");
    }

    /**
     * Verify that a user can delete their own comment if the "AllowSelfDelete" config setting is true.
     */
    public function testDeletingOwnCommentAllowed()
    {
        $this->runWithConfig(["Vanilla.Comments.AllowSelfDelete" => true], function () {
            $userID = $this->createUserFixture(self::ROLE_MEMBER);
            $this->api()->setUserID($userID);
            $comment = $this->api()
                ->post("comments", [
                    "body" => 'You can\'t delete this',
                    "format" => "text",
                    "discussionID" => 1,
                ])
                ->getBody();

            $this->api()->delete("comments/{$comment["commentID"]}");

            $this->expectExceptionCode(404);
            $this->expectExceptionMessage("Comment not found.");

            $this->api()->get("comments/{$comment["commentID"]}");
        });
    }

    /**
     * Test commenting on a closed discussion.
     */
    public function testCommentClosedDiscussion()
    {
        $discussion = $this->createDiscussion();
        $discussionID = $discussion["discussionID"];
        $this->api()->patch("/discussions/{$discussionID}", ["closed" => 1]);
        $this->api()->post("/comments", [
            "body" => "an admin can post a comment on a closed discussion.",
            "discussionID" => $discussionID,
            "format" => "text",
        ]);
        $comments = $this->api()
            ->get("/comments?discussionID={$discussionID}&sort=-dateInserted")
            ->getBody();
        $commentBody = $comments[0]["body"];
        $this->assertSame("an admin can post a comment on a closed discussion.", $commentBody);

        // Create a member-level user and try the same thing.
        $username = substr(__FUNCTION__, 0, 20);
        $user = $this->api()
            ->post("users", [
                "name" => $username,
                "email" => $username . "@example.com",
                "password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            ])
            ->getBody();

        $this->api()->setUserID($user["userID"]);
        $this->expectExceptionMessage("This discussion has been closed.");
        $this->api()->post("/comments", [
            "body" => "a member cannot post on a closed discussion.",
            "discussionID" => $discussionID,
            "format" => "markdown",
        ]);
    }

    /**
     * Test editing a comment.
     */
    public function testCommentCanEdit(): void
    {
        $user = $this->createUser();
        $comment = $this->runWithUser(function () {
            $discussion = $this->createDiscussion();
            $comment = $this->createComment(["discussionID" => $discussion["discussionID"]]);
            $this->api()->post("/comments/{$comment["commentID"]}", ["body" => "edited comment"]);
            $result = $this->api()
                ->get("/comments/{$comment["commentID"]}")
                ->getBody();
            $this->assertEquals("edited comment", $result["body"]);
            return $comment;
        }, $user);
        $this->runWithConfig(
            [
                "Garden.EditContentTimeout" => "0",
            ],
            function () use ($user, $comment) {
                \Gdn::session()->start($user["userID"]);
                $this->expectExceptionMessage("Editing comments is not allowed.");
                $this->expectExceptionCode(400);
                $this->api()->post("/comments/{$comment["commentID"]}", ["body" => "edited comment2"]);
            }
        );
    }

    /**
     * Test to ensure the comment draft is cleared after posting a comment.
     * @return void
     */
    public function testPostingCommentClearsDraft(): void
    {
        $discussionID = 1;
        $commentBody = "This is a comment";

        // Save a draft
        $draftResponse = $this->api()
            ->post("/drafts", [
                "attributes" => [
                    "format" => "markdown",
                    "body" => $commentBody,
                ],
                "parentRecordID" => $discussionID,
                "recordType" => "comment",
            ])
            ->getBody();
        $draftID = $draftResponse["draftID"];

        //Fetch the users drafts
        $allDrafts = $this->api()
            ->get("/drafts")
            ->getBody();

        // Expect 1 draft saved for the user
        $this->assertCount(1, $allDrafts);

        // Post the comment
        $this->api()->post("/comments", [
            "body" => $commentBody,
            "discussionID" => $discussionID,
            "format" => "markdown",
            "draftID" => $draftID,
        ]);

        // Fetch the users drafts again
        $allDrafts = $this->api()
            ->get("/drafts")
            ->getBody();

        // Expect the draft to be deleted
        $this->assertEmpty($allDrafts);
    }

    /**
     * Test expanding attachments via the "/comments" endpoint.
     *
     * @return void
     */
    public function testExpandCommentsAttachments(): void
    {
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();
        $attachment = $this->createAttachment("comment", $comment["commentID"]);
        $result = $this->api()
            ->get($this->baseUrl, ["discussionID" => $discussion["discussionID"], "expand" => "attachments"])
            ->getBody();
        $retrievedComment = $result[0];
        $this->assertArrayHasKey("attachments", $retrievedComment);
        $this->assertEquals($attachment["AttachmentID"], $retrievedComment["attachments"][0]["attachmentID"]);
    }

    /**
     * Test expanding attachments via the "/comments/{commentID}" endpoint.
     *
     * @return void
     */
    public function testExpandCommentAttachments(): void
    {
        $this->createDiscussion();
        $comment = $this->createComment();
        $attachment = $this->createAttachment("comment", $comment["commentID"]);
        $result = $this->api()
            ->get("{$this->baseUrl}/{$comment["commentID"]}", ["expand" => "attachments"])
            ->getBody();
        $this->assertArrayHasKey("attachments", $result);
        $this->assertCount(1, $result["attachments"]);
        $this->assertEquals($attachment["AttachmentID"], $result["attachments"][0]["attachmentID"]);
    }

    /**
     * Test that expanding attachments via the "/comments/{commentID}" endpoint without the "Garden.Staff.Allow" permission
     * returns no attachments.
     *
     * @return void
     */
    public function testExpandCommentAttachmentsWithoutPermission(): void
    {
        $this->createDiscussion();
        $comment = $this->createComment();
        $this->createAttachment("comment", $comment["commentID"]);
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $discussion = $this->api()
            ->get("{$this->baseUrl}/{$comment["commentID"]}", ["expand" => "attachments"])
            ->getBody();
        $this->assertArrayNotHasKey("attachments", $discussion);
    }

    /**
     *  Test that expanding attachments via the "/comments" endpoint without the "Garden.Staff.Allow" permission
     *  returns no attachments.
     *
     * @return void
     */
    public function testExpandCommentsAttachmentsWithoutPermission(): void
    {
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();
        $this->createAttachment("comment", $comment["commentID"]);
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $result = $this->api()
            ->get($this->baseUrl, ["discussionID" => $discussion["discussionID"], "expand" => "attachments"])
            ->getBody();
        $retrievedComment = $result[0];
        $this->assertArrayNotHasKey("attachments", $retrievedComment);
    }

    /**
     * Test filtering comments by userRoleID.
     *
     * @return void
     */
    public function testGettingCommentsByRoleID(): void
    {
        $category = $this->createCategory();
        $discussion = $this->createDiscussion(["categoryID" => $category["categoryID"]]);

        $newRole = $this->createRole([
            "permissions" => [
                [
                    "permissions" => [
                        "session.valid" => true,
                    ],
                    "type" => "global",
                ],
                [
                    "id" => $category["categoryID"],
                    "permissions" => [
                        "discussions.view" => true,
                        "discussions.add" => true,
                        "comments.add" => true,
                    ],
                    "type" => "category",
                ],
            ],
        ]);

        $this->runWithUser(
            function () use ($discussion) {
                $this->createComment(["discussionID" => $discussion["discussionID"]]);
            },
            $this->createUser([
                "roleID" => [$newRole["roleID"]],
            ])
        );

        $comments = $this->api()
            ->get("comments", ["insertUserRoleID" => [$newRole["roleID"]]])
            ->getBody();
        $this->assertCount(1, $comments);
    }

    /**
     * Test filtering comments by categoryID.
     *
     * @return void
     */
    public function testGetCommentsByCategoryID()
    {
        $category = $this->createCategory();
        $discussion = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $comment = $this->createComment(["discussionID" => $discussion["discussionID"]]);

        $allComments = $this->api()
            ->get("comments")
            ->getBody();

        $this->assertTrue(count($allComments) > 1);

        // There are more than one comment, but only one comment in the category.
        $comments = $this->api()
            ->get("comments?categoryID={$category["categoryID"]}")
            ->getBody();
        $this->assertCount(1, $comments);
        $this->assertEquals($comment["commentID"], $comments[0]["commentID"]);
    }

    /**
     * Test category permissions are respected when filtering by categoryID.
     *
     * @return void
     */
    public function testCategoryViewPermission(): void
    {
        $category = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID, \RoleModel::MOD_ID]);
        $discussion = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $this->createComment(["discussionID" => $discussion["discussionID"]]);
        $this->api()->setUserID($this->createUserFixture(self::ROLE_MEMBER));
        $comments = $this->api()
            ->get("comments?categoryID={$category["categoryID"]}")
            ->getBody();
        // There should be no comments, since the user does not have permission to view the category.
        $this->assertEmpty($comments);
    }

    /**
     * Test that sysadmins can always access comments.
     *
     * @return void
     */
    public function testSysAdminCanAlwaysAccessComments(): void
    {
        $permissionedCategory = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID, \RoleModel::MOD_ID]);
        $discussion = $this->createDiscussion(["categoryID" => $permissionedCategory["categoryID"]]);
        $comment = $this->createComment(["discussionID" => $discussion["discussionID"]]);

        $userWithNoRoles = $this->createUser();
        $sysAdminWithNoRoles = $this->createUser([], ["Admin" => 2]);

        $freshlyFetchedSysAdmin = $this->api()
            ->get("users/{$sysAdminWithNoRoles["userID"]}")
            ->getBody();

        // Verify that this user is a sysadmin
        $this->assertTrue($freshlyFetchedSysAdmin["isSysAdmin"]);

        $this->api()->setUserID($freshlyFetchedSysAdmin["userID"]);
        $result = $this->api()
            ->get("comments?discussionID={$discussion["discussionID"]}")
            ->getBody();

        // Verify that the sysadmin can access the comment
        $this->assertCount(1, $result);
        $this->assertSame($comment["commentID"], $result[0]["commentID"]);

        // A user without the permission cannot access the comment.
        $this->api()->setUserID($userWithNoRoles["userID"]);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $comments = $this->api()
            ->get("comments?discussionID={$discussion["discussionID"]}")
            ->getBody();
    }

    /**
     * Ensure that there are dirtyRecords for a specific resource.
     */
    protected function triggerDirtyRecords()
    {
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment2 = $this->createComment();

        /** @var CommentModel $commentModel */
        $commentModel = \Gdn::getContainer()->get(CommentModel::class);

        $commentModel->setField($comment1["commentID"], "Score", 10);
        $commentModel->setField($comment2["commentID"], "Score", 5);
    }

    /**
     * Get the resource type.
     *
     *
     * @return array
     */
    protected function getResourceInformation(): array
    {
        return [
            "resourceType" => "comment",
            "primaryKey" => "commentID",
        ];
    }

    /**
     * Get the api query.
     *
     * @return array
     */
    protected function getQuery(): array
    {
        return [
            DirtyRecordModel::DIRTY_RECORD_OPT => true,
            "insertUserID" => $this->api()->getUserID(),
        ];
    }
}
