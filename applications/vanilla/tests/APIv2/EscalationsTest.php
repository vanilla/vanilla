<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for /api/v2/escalations
 */
class EscalationsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        CurrentTimeStamp::mockTime("2024-01-01");
    }

    /**
     * This test is first so we don't have any extra users we have to worry about.
     *
     * @return void
     */
    public function testLookupAssignee(): void
    {
        $globalMod1 = $this->createGlobalMod(["name" => "Adam Global Mod"]);
        $globalMod2 = $this->createGlobalMod(["name" => "Adam Other Global Mod"]);
        $cat = $this->createCategory(["name" => "My Category"]);
        $catMod = $this->createCategoryMod($cat, ["name" => "Adam Cat Mod"]);
        $regularUser = $this->createUser(["name" => "Adam Member"]);
        $post = $this->createDiscussion();
        $escalation = $this->createEscalation($post);

        $cat2 = $this->createCategory(["name" => "Other Category"]);
        $cat2Mod = $this->createCategoryMod($cat2, ["name" => "Adam Other Cat Mod"]);

        // If we don't pass the escalationID it will pull all people that could have an escalation.
        $this->assertApiResults(
            "/escalations/lookup-assignee",
            ["name" => "Adam*"],
            [
                "name" => [$globalMod1["name"], $globalMod2["name"], $catMod["name"], $cat2Mod["name"]],
            ]
        );

        // We don't have to pass a name to this endpoint.
        $this->assertApiResults(
            "/escalations/lookup-assignee",
            [
                "escalationID" => $escalation["escalationID"],
            ],
            [
                // Global Admins are included too
                "name" => [$globalMod1["name"], $globalMod2["name"], $catMod["name"], "System", "circleci"],
            ]
        );

        // Wildcard name match
        $this->assertApiResults(
            "/escalations/lookup-assignee",
            ["name" => "Adam*", "escalationID" => $escalation["escalationID"]],
            [
                "name" => [$globalMod1["name"], $globalMod2["name"], $catMod["name"]],
            ]
        );

        // Exact name match
        $this->assertApiResults(
            "/escalations/lookup-assignee",
            ["name" => "Adam Global Mod", "escalationID" => $escalation["escalationID"]],
            [
                "name" => [$globalMod1["name"]],
            ]
        );

        // Ensure that searching specifically for an assignee that isn't valid will not show them.
        $this->assertApiResults(
            "/escalations/lookup-assignee",
            ["name" => "Adam Member"],
            [
                "name" => [],
            ]
        );
    }

    /**
     * Test that we can create an escalation from one or more reports.
     */
    public function testCreateEscalationsFromReports(): void
    {
        $this->createCategory(["name" => "my cat"]);
        $discussion = $this->createDiscussion(["name" => "Bad Post"]);
        $report1 = $this->createReport($discussion, ["reportReasonIDs" => ["abuse"]]);
        $report2 = $this->createReport($discussion, ["reportReasonIDs" => ["spam"]]);
        $escalation = $this->api()->post("/escalations", [
            "recordType" => "discussion",
            "recordID" => $discussion["discussionID"],
            "name" => "What a bad post!",
            "reportID" => $report1["reportID"],
        ]);

        $this->assertDataLike(
            [
                "name" => "What a bad post!",
                "placeRecordName" => "my cat",
                "recordIsLive" => true,
                "status" => EscalationModel::STATUS_OPEN,
                "recordUrl" => $discussion["url"],
                "countReports" => 2,
                "reportReasonIDs" => ["abuse", "spam"],
            ],
            $escalation->getBody()
        );
    }

    /**
     * Test that creating an escalation can create a report automatically.
     */
    public function testCreateEscalationWithoutReport()
    {
        // We can make an escalation directly
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $escalation = $this->api()
            ->post("/escalations", [
                "recordType" => "discussion",
                "recordID" => $discussion["discussionID"],
                "name" => "What a bad post 2!",
                "reportReasonIDs" => ["spam"],
                "noteFormat" => "markdown",
                "noteBody" => "so bad",
            ])
            ->getBody();

        $this->assertDataLike(
            [
                "name" => "What a bad post 2!",
                "recordIsLive" => true,
                "status" => EscalationModel::STATUS_OPEN,
                "recordUrl" => $discussion["url"],
                "countReports" => 1,
                "reportReasonIDs" => ["spam"],
            ],
            $escalation
        );

        // We can get the escalation
        $fetched = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals($escalation, $fetched);
    }

    /**
     * Test listing of escalations.
     */
    public function testListEscalations()
    {
        $this->createCategory();
        $disc1 = $this->createDiscussion();
        $esc1 = $this->createEscalation($disc1);
        $disc2 = $this->createDiscussion();
        $esc2 = $this->createEscalation($disc2);

        $escalations = $this->api()
            ->get("/escalations", ["placeRecordType" => "category", "placeRecordID" => $this->lastInsertedCategoryID])
            ->getBody();
        $this->assertCount(2, $escalations);
        $this->assertRowsLike(
            [
                "recordType" => ["discussion", "discussion"],
                "recordID" => [$disc1["discussionID"], $disc2["discussionID"]],
            ],
            $escalations
        );
    }

    /**
     * Test that escalations can create and restore posts.
     */
    public function testEscalationRemoveRestorePost()
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();
        $report = $this->createReport($comment, ["reportReasonIDs" => ["abuse"]]);

        $escalation = $this->createEscalation($comment, [
            "reportID" => $report["reportID"],
            "recordIsLive" => false,
            "name" => "What a bad post!",
        ]);
        $this->assertDataLike(
            [
                "name" => "What a bad post!",
                "recordIsLive" => false,
                "status" => EscalationModel::STATUS_OPEN,
                "countReports" => 1,
                "reportReasonIDs" => ["abuse"],
            ],
            $escalation
        );

        $this->runWithExpectedExceptionCode(404, function () use ($comment) {
            $this->api()->get("/comments/{$comment["commentID"]}");
        });

        // now restore it
        $patched = $this->api()
            ->patch("/escalations/{$escalation["escalationID"]}", ["recordIsLive" => true])
            ->getBody();

        $this->assertDataLike(
            [
                "recordIsLive" => true,
                "recordUrl" => $comment["url"],
            ],
            $patched
        );

        // We should be able to do the same thing for a discussion
        $discEscalation = $this->createEscalation($discussion, [
            "recordIsLive" => false,
            "name" => "Bad discussion!",
        ]);
        $this->assertDataLike(
            [
                "name" => "Bad discussion!",
                "recordIsLive" => false,
                "status" => EscalationModel::STATUS_OPEN,
                "countReports" => 1,
            ],
            $discEscalation
        );

        $this->runWithExpectedExceptionCode(410, function () use ($discussion) {
            $this->api()->get("/discussions/{$discussion["discussionID"]}");
        });
        // Comment should have been removed too
        $this->runWithExpectedExceptionCode(404, function () use ($comment) {
            $this->api()->get("/comments/{$comment["commentID"]}");
        });

        // If I restore the post, the comments come back
        $patched = $this->api()
            ->patch("/escalations/{$discEscalation["escalationID"]}", [
                "recordIsLive" => true,
            ])
            ->getBody();

        $discussion = $this->api()->get("/discussions/{$discussion["discussionID"]}");
        $comment = $this->api()->get("/comments/{$comment["commentID"]}");
        $this->assertEquals(200, $comment->getStatusCode());
    }

    /**
     * Test that we can unassign users.
     *
     * @return void
     */
    public function testUnassignUser(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $escalation = $this->createEscalation($discussion, [
            "assignedUserID" => $this->api->getUserID(),
        ]);

        $this->assertEquals($this->api->getUserID(), $escalation["assignedUserID"]);

        // No remove the assigned user.
        $patched = $this->api()
            ->patch("/escalations/{$escalation["escalationID"]}", [
                "assignedUserID" => EscalationModel::UNASSIGNED_USER_ID,
            ])
            ->getBody();

        $this->assertEquals(EscalationModel::UNASSIGNED_USER_ID, $patched["assignedUserID"]);
    }

    /**
     * Test that we can properly filter for assigned users.
     */
    public function testFilters(): void
    {
        CurrentTimeStamp::mockTime("2023-10-10");
        $this->resetTable("escalation");
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();

        $this->createCategory();
        $discussion1 = $this->createDiscussion();
        $comment1 = $this->createComment();

        $cat2 = $this->createCategory();
        $discussion2 = $this->createDiscussion();

        $escalation1 = $this->createEscalation($discussion1, [
            "assignedUserID" => $user1["userID"],
            "status" => EscalationModel::STATUS_ON_HOLD,
        ]);

        $escalation2 = $this->createEscalation($discussion2, [
            "assignedUserID" => $user2["userID"],
            "status" => EscalationModel::STATUS_DONE,
        ]);

        $escalation3 = $this->createEscalation($comment1, [
            "assignedUserID" => $user3["userID"],
            "status" => EscalationModel::STATUS_IN_PROGRESS,
        ]);

        $unassignedEscalation = $this->createEscalation($comment1);

        // Filter by assigned userID.
        $this->assertEscalations(["assignedUserID" => $user1["userID"]], [$escalation1]);
        $this->assertEscalations(["assignedUserID" => EscalationModel::UNASSIGNED_USER_ID], [$unassignedEscalation]);
        $this->assertEscalations(
            ["assignedUserID" => [$user1["userID"], $user2["userID"], EscalationModel::UNASSIGNED_USER_ID]],
            [$escalation1, $escalation2, $unassignedEscalation]
        );

        // Filter by recordType
        $this->assertEscalations(["recordType" => "discussion"], [$escalation1, $escalation2]);

        // Filter by recordType & recordID
        $this->assertEscalations(
            ["recordType" => "discussion", "recordID" => $discussion1["discussionID"]],
            [$escalation1]
        );

        // Filter by status
        $this->assertEscalations(["status" => EscalationModel::STATUS_DONE], [$escalation2]);
        // Filter by multiple statuses
        $this->assertEscalations(
            ["status" => [EscalationModel::STATUS_DONE, EscalationModel::STATUS_ON_HOLD]],
            [$escalation1, $escalation2]
        );

        // Filter by categoryID
        $this->assertEscalations(
            ["placeRecordType" => "category", "placeRecordID" => $cat2["categoryID"]],
            [$escalation2]
        );
    }

    /**
     * Test that pagination works on the escalation endpoints.
     *
     * @return void
     */
    public function testPagination(): void
    {
        $this->resetTable("escalation");
        $this->createCategory();
        $discussion = $this->createDiscussion();

        for ($i = 0; $i < 3; $i++) {
            $this->createEscalation($discussion);
        }

        $response = $this->api()->get("/escalations", ["limit" => 1]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $response->getBody());
        $this->assertEquals(3, $response->getHeader("x-app-page-result-count"));
        $this->assertEquals(
            "https://vanilla.test/escalationstest/api/v2/escalations?page=2&limit=1",
            $response->getHeader("x-app-page-next-url")
        );

        // Get the next page
        $response = $this->api()->get($response->getHeader("x-app-page-next-url"));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $response->getBody());
        $this->assertEquals(3, $response->getHeader("x-app-page-result-count"));
        $this->assertEquals(
            "https://vanilla.test/escalationstest/api/v2/escalations?page=1&limit=1",
            $response->getHeader("x-app-page-prev-url")
        );
    }

    /**
     * Test that we can filter by report reasons.
     */
    public function testReportReasonFilters(): void
    {
        $this->resetTable("escalation");
        $this->resetTable("report");
        $this->createCategory();

        $disc1 = $this->createDiscussion();
        $report1 = $this->createReport($disc1, ["reportReasonIDs" => ["spam"]]);
        $report2 = $this->createReport($disc1, ["reportReasonIDs" => ["abuse"]]);
        $esc1 = $this->createEscalation($disc1, ["reportID" => $report1["reportID"]]);

        $disc2 = $this->createDiscussion();
        $esc2 = $this->createEscalation($disc2, ["reportReasonIDs" => ["abuse", "inappropriate"]]);

        $this->assertEscalations(["reportReasonID" => ["spam"]], [$esc1]);
        $this->assertEscalations(["reportReasonID" => ["abuse"]], [$esc1, $esc2]);
        $this->assertEscalations(["reportReasonID" => ["abuse", "inappropriate"]], [$esc1, $esc2]);
        $this->assertEscalations(["reportReasonID" => ["inappropriate"]], [$esc2]);
    }

    /**
     * Test that we can filter by recordUserID and recordUserRoleID
     */
    public function testUserFilters(): void
    {
        $this->createCategory();
        $modUser = $this->createUser(["roleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID]]);
        $memberUser = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);

        $modDiscussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $modUser);

        $memberDiscussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $memberUser);

        CurrentTimeStamp::mockTime("2024-01-01");
        $modEscalation = $this->createEscalation($modDiscussion);
        $memberEscalation = $this->createEscalation($memberDiscussion);

        $this->assertEscalations(["recordUserID" => $modUser["userID"]], [$modEscalation]);
        $this->assertEscalations(["recordUserID" => $memberUser["userID"]], [$memberEscalation]);
        $this->assertEscalations(
            ["recordUserID" => [$memberUser["userID"], $modUser["userID"]]],
            [$modEscalation, $memberEscalation]
        );
        $this->assertEscalations(["recordUserRoleID" => \RoleModel::MOD_ID], [$modEscalation]);
        $this->assertEscalations(["recordUserRoleID" => \RoleModel::MEMBER_ID], [$modEscalation, $memberEscalation]);
    }

    /**
     * Assert that we can query the escalations API and get certain results.
     *
     * @param array $params
     * @param array $expectedEscalations
     */
    private function assertEscalations(array $params, array $expectedEscalations): void
    {
        $response = $this->api()->get("/escalations", $params);
        $this->assertEquals(200, $response->getStatusCode());
        $escalations = $response->getBody();

        $expectedEscalationIDs = array_column($expectedEscalations, "escalationID");
        $actualEscalationIDs = array_column($escalations, "escalationID");
        $this->assertEquals($expectedEscalationIDs, $actualEscalationIDs, "Did not find expected escalations.");
    }

    /**
     * Test creation and refeshing of attachments.
     *
     * @return void
     */
    public function testEscalationAttachment(): void
    {
        $date = CurrentTimeStamp::mockTime("2024-01-01");
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createReport($discussion, ["reportReasonIDs" => ["spam"]]);
        $escalation = $this->createEscalation($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", [
                "expand" => "attachments",
            ])
            ->getBody();

        $this->assertCount(1, $discussion["attachments"]);

        $attachment = $discussion["attachments"][0];
        $expectedMeta = [
            [
                "labelCode" => "Name",
                "value" => "This is an escalation",
            ],
            [
                "labelCode" => "# Reports",
                "value" => 2,
            ],
            [
                "labelCode" => "Last Reported",
                "value" => $date->format(\DateTime::RFC3339_EXTENDED),
                "format" => "date-time",
            ],
            [
                "labelCode" => "Report Reasons",
                "value" => ["Spam / Solicitation", "Abuse"],
            ],
            [
                "labelCode" => "Last Modified",
                "value" => $date->format(\DateTime::RFC3339_EXTENDED),
                "format" => "date-time",
            ],
        ];
        $this->assertSame($expectedMeta, $attachment["metadata"]);
        $this->assertEquals("open", $attachment["status"]);
        $this->api()->patch("/escalations/{$escalation["escalationID"]}", [
            "status" => "in-progress",
        ]);

        // We can refresh attachments too.
        $this->api()->post("/attachments/refresh", [
            "attachmentIDs" => [$attachment["attachmentID"]],
        ]);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", [
                "expand" => "attachments",
            ])
            ->getBody();

        $attachment = $discussion["attachments"][0];
        $this->assertEquals("in-progress", $attachment["status"]);
    }

    /**
     * Test CRUD from comments on escalations.
     *
     * @return void
     */
    public function testCommentsCrud()
    {
        $this->resetTable("Comment");
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createComment();
        $escalation = $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);

        // Now I can comment on the escalation.
        $comment = $this->createEscalationComment();
        $expectedComment = [
            "parentRecordID" => $escalation["escalationID"],
            "parentRecordType" => "escalation",
            "name" => "Re: My Escalation",
            "url" => url(
                "/dashboard/content/escalations/{$escalation["escalationID"]}?commentID={$comment["commentID"]}",
                true
            ),
            "body" => "Hello Comment",
            "discussionID" => null,
            "categoryID" => $category["categoryID"],
        ];
        $this->assertDataLike($expectedComment, $comment);

        $this->runWithExpectedExceptionMessage("cannot be reported", function () use ($comment) {
            $this->createReport($comment);
        });

        // We can patch the comment
        $this->api()->patch("/comments/{$comment["commentID"]}", [
            "body" => "This is a new body",
        ]);
        $expectedComment["body"] = "This is a new body";

        // We can fetch the comment
        $comment = $this->api()
            ->get("/comments/{$comment["commentID"]}")
            ->getBody();
        $this->assertDataLike($expectedComment, $comment);

        // We can fetch the comment from the index by ID.
        $comment = $this->api()
            ->get("/comments", ["commentID" => $comment["commentID"]])
            ->getBody()[0];
        $this->assertDataLike($expectedComment, $comment);

        // We can fetch the comment from the index by parentRecordID.
        $comments = $this->api()
            ->get("/comments", ["parentRecordType" => "escalation", "parentRecordID" => $escalation["escalationID"]])
            ->getBody();
        $this->assertCount(1, $comments);

        $this->assertDataLike($expectedComment, $comments[0]);

        // Our escalation comment count should have been incremented.
        $escalation = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals(1, $escalation["countComments"]);

        // And finally we can delete the comment
        $this->api()->deleteWithBody("/comments/list", [
            "commentIDs" => [$comment["commentID"]],
            "deleteMethod" => "full",
        ]);

        $this->runWithExpectedExceptionCode(404, function () use ($comment) {
            $this->api()->get("/comments/{$comment["commentID"]}");
        });

        // Our escalation comment count should have been decremented.
        $escalation = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals(0, $escalation["countComments"]);
    }

    /**
     * Test that we can create an initial comment when making an escalation.
     *
     * @return void
     */
    public function testCreateEscalationWithInitialComment(): void
    {
        $cat = $this->createCategory();
        $discussion = $this->createDiscussion();
        $escalation = $this->createEscalation($discussion, [
            "initialCommentBody" => "This is an initial comment",
            "initialCommentFormat" => "text",
        ]);
        $this->assertEquals(1, $escalation["countComments"]);

        $comments = $this->api()
            ->get("/comments", ["parentRecordType" => "escalation", "parentRecordID" => $escalation["escalationID"]])
            ->getBody();
        $this->assertCount(1, $comments);
        $comment = $comments[0];

        $this->assertDataLike(
            [
                "parentRecordID" => $escalation["escalationID"],
                "parentRecordType" => "escalation",
                "body" => "This is an initial comment",
                "categoryID" => $cat["categoryID"],
            ],
            $comment
        );
    }

    /**
     * Test getting escalation comments as a moderator.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testGetEscalationComment(): void
    {
        $this->resetTable("Comment");
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createComment();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $comment = $this->createEscalationComment();
        $moderator = $this->createUser(["roleID" => [\RoleModel::MOD_ID]]);

        $this->runWithUser(function () use ($comment) {
            $result = $this->api()
                ->get("/comments")
                ->getBody();
            $this->assertEquals(
                2,
                count($result),
                "The user should able to see the escalation comment when calling [GET] `/api/v2/comments`."
            );

            $result = $this->api()->get("/comments/{$comment["commentID"]}");
            $this->assertEquals(
                200,
                $result->getStatusCode(),
                "The user should able to see the escalation comment when calling [GET] `/api/v2/comments/{$comment["commentID"]}`."
            );

            $this->assertEquals($comment["commentID"], $result->getBody()["commentID"]);
        }, $moderator);
    }

    /**
     * Test that countComments, lastCommentUserID, and dateLastCommentUserID are accurate comment is added or removed.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testEscalationCommentData(): void
    {
        $this->resetTable("Comment");
        $this->createCategory();
        $user1 = $this->createUser(["name" => "user1", "roleID" => [\RoleModel::MOD_ID]]);
        $user2 = $this->createUser(["name" => "user2", "roleID" => [\RoleModel::MOD_ID]]);

        $discussion = $this->createDiscussion();
        $this->createComment();
        $escalation = $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $comment1 = $this->createEscalationComment(["insertUserID" => $user1["userID"]]);
        $comment2 = $this->createEscalationComment(["insertUserID" => $user2["userID"]]);

        $escalation = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals(2, $escalation["countComments"]);
        $this->assertEquals($user2["userID"], $escalation["lastCommentUserID"]);
        $this->assertEquals($comment2["commentID"], $escalation["lastCommentID"]);
        $this->assertEquals($comment2["dateInserted"], $escalation["dateLastComment"]);

        $this->api()->delete("/comments/{$comment2["commentID"]}");

        $escalation = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals(1, $escalation["countComments"]);
        $this->assertEquals($comment1["insertUserID"], $escalation["lastCommentUserID"]);
        $this->assertEquals($comment1["commentID"], $escalation["lastCommentID"]);
        $this->assertEquals($comment1["dateInserted"], $escalation["dateLastComment"]);

        $this->api()->delete("/comments/{$comment1["commentID"]}");

        $escalation = $this->api()
            ->get("/escalations/{$escalation["escalationID"]}")
            ->getBody();
        $this->assertEquals(0, $escalation["countComments"]);
        $this->assertEquals(null, $escalation["lastCommentID"]);
        $this->assertEquals(null, $escalation["lastCommentUserID"]);
        $this->assertEquals(null, $escalation["dateLastComment"]);
    }

    /**
     * Test that a user with insufficient permission is not allowed to see the escalation comments.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testGetEscalationCommentNoPermission(): void
    {
        $this->resetTable("Comment");
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createComment();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $comment = $this->createEscalationComment();
        $member = $this->createUser();

        $this->runWithUser(function () use ($comment) {
            // The user is not able to see when calling the index.
            $result = $this->api()
                ->get("/comments")
                ->getBody();
            $this->assertEquals(
                1,
                count($result),
                "The user should not be able to see the escalation comment when calling [GET] `/api/v2/comments`."
            );

            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->get("/comments/{$comment["commentID"]}");
        }, $member);
    }

    /**
     * Test creating an escalation comment as an unauthorized user.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testPostEscalationCommentNoPermission(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $member = $this->createUser();

        $this->runWithUser(function () {
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->createEscalationComment();
        }, $member);
    }

    /**
     * Test editing an escalation comment as an unauthorized user.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testPatchEscalationCommentNoPermission(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $member = $this->createUser();
        $comment = $this->createEscalationComment(["insertUserID" => $member["userID"]]);

        $this->runWithUser(function () use ($comment) {
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->patch("/comments/{$comment["commentID"]}", ["body" => "New Body"]);
        }, $member);
    }

    /**
     * Test deleting an escalation comment as an unauthorized user.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testDeleteEscalationCommentNoPermission(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $member = $this->createUser();
        $comment = $this->createEscalationComment(["insertUserID" => $member["userID"]]);

        $this->runWithUser(function () use ($comment) {
            $this->expectException(ClientException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->delete("/comments/{$comment["commentID"]}");
        }, $member);
    }

    /**
     * Test reacting on an escalation comment.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testReactOnEscalationComment(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $comment = $this->createEscalationComment();
        $this->api()->post("/comments/{$comment["commentID"]}/reactions", ["reactionType" => "like"]);
        $result = $this->api()
            ->get("/comments/{$comment["commentID"]}", ["expand" => "reactions"])
            ->getBody();

        $this->assertEquals(1, $result["score"]);
        foreach ($result["reactions"] as $reaction) {
            if ($reaction["name"] === "Like") {
                $this->assertEquals(1, $reaction["count"]);
                break;
            }
        }
    }

    /**
     * Test reacting on a comment without the proper permissions.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testReactingNoPermission(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $this->createEscalation($discussion, [
            "name" => "My Escalation",
        ]);
        $comment = $this->createEscalationComment();
        $user = $this->createUser();

        $this->runWithUser(function () use ($comment) {
            $this->expectException(ForbiddenException::class);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->post("/comments/{$comment["commentID"]}/reactions", [
                "reactionType" => "like",
            ]);
        }, $user);
    }
}
