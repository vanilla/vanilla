<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum;

use Vanilla\Dashboard\Models\RecordStatusModel;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/discussions with resolved2
 */
class ResolvedDiscussionsTest extends SiteTestCase
{
    use CommunityApiTestTrait, UsersAndRolesApiTestTrait, QnaApiTestTrait;

    protected static $addons = ["QnA"];

    /**
     * @var \DiscussionModel
     */
    private $discussionModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
    }

    /**
     * Test /discussions?resolved={true|false}
     */
    public function testIndexResolvedFilter()
    {
        $this->createDiscussion();
        $this->createDiscussion();
        $this->createDiscussion();

        // The testIndex() method added a few default rows for us, none of which should be marked as resolved
        $resultResolved = $this->api()->get("/discussions", ["resolved" => true]);
        $this->assertEquals(200, $resultResolved->getStatusCode());
        $resolvedDiscussions = $resultResolved->getBody();
        $this->assertEmpty($resolvedDiscussions);

        // Check that all the posts that got added via testIndex are marked as unresolved
        $resultUnresolved = $this->api()->get("/discussions", ["resolved" => false]);
        $this->assertEquals(200, $resultResolved->getStatusCode());
        $unresolvedDiscussions = $resultUnresolved->getBody();
        $numUnresolvedDiscussions = count($unresolvedDiscussions);
        $this->assertGreaterThanOrEqual(3, $numUnresolvedDiscussions);

        // Add two posts, one of which is resolved, the other of which is not resolved
        $this->createDiscussion(["resolved" => true]);
        $this->createDiscussion(["resolved" => false]);

        // Get all the resolved discussions now that one exists
        $resultResolved = $this->api()->get("/discussions", ["resolved" => true]);
        $this->assertEquals(200, $resultResolved->getStatusCode());
        $resolvedDiscussions = $resultResolved->getBody();
        $this->assertCount(1, $resolvedDiscussions);
        foreach ($resolvedDiscussions as $resolvedDiscussion) {
            $this->assertEquals(true, $resolvedDiscussion["resolved"]);
            $this->assertEquals(RecordStatusModel::DISCUSSION_STATUS_RESOLVED, $resolvedDiscussion["internalStatusID"]);
        }

        // Get all the unresolved discussions and ensure the one we added is present in this set
        $resultUnresolved = $this->api()->get("/discussions", ["resolved" => false]);
        $this->assertEquals(200, $resultResolved->getStatusCode());
        $unresolvedDiscussions = $resultUnresolved->getBody();
        $this->assertCount($numUnresolvedDiscussions + 1, $unresolvedDiscussions);
        foreach ($unresolvedDiscussions as $unresolvedDiscussion) {
            $this->assertEquals(false, $unresolvedDiscussion["resolved"]);
            $this->assertEquals(
                RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED,
                $unresolvedDiscussion["internalStatusID"]
            );
        }
    }

    /**
     * Test /discussions GET|POST|PATCH with non Staff user.
     */
    public function testNonStaffUserCannotUseResolvedField()
    {
        self::container()->setInstance(\DiscussionsApiController::class, null);
        $userID = $this->createUserFixture(self::ROLE_MEMBER);
        $api = $this->api()->setUserID($userID);
        // Post discussion with resolved = true
        $discussionCreated = $this->createDiscussion(["resolved" => true]);
        $discussionID = $discussionCreated["discussionID"];
        $this->assertArrayNotHasKey("internalStatusID", $discussionCreated);
        $discussionCreatedDB = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        // Use cannot set resolved by default it will be false.
        $this->assertEquals(RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED, $discussionCreatedDB["internalStatusID"]);

        // Patch discussion with resolved = true
        $response = $api->patch("/discussions/" . $discussionID, ["resolved" => true]);
        $this->assertEquals(200, $response->getStatusCode());
        $discussionData = $response->getBody();
        $this->assertArrayNotHasKey("internalStatusID", $discussionData);
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        // Use cannot set resolved by default it will be false.
        $this->assertEquals(RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED, $discussion["internalStatusID"]);

        // Get index.
        $response = $api->get("/discussions");
        $this->assertEquals(200, $response->getStatusCode());
        $discussions = $response->getBody();
        foreach ($discussions as $discussion) {
            $this->assertArrayNotHasKey("resolved", $discussion);
        }

        self::container()->setInstance(\DiscussionsApiController::class, null);
    }

    /**
     * Test that the "resolved" status of an unresolved discussion is not changed when another unresolved discussion is merged into it.
     */
    public function testMergingTwoUnresolvedDiscussions(): void
    {
        $unresolvedOne = $this->createDiscussion([]);
        $unresolvedOneID = $unresolvedOne["discussionID"];
        $unresolvedTwo = $this->createDiscussion([]);
        $unresolvedTwoID = $unresolvedTwo["discussionID"];

        $this->api()
            ->patch("/discussions/merge", [
                "discussionIDs" => [$unresolvedTwoID],
                "destinationDiscussionID" => $unresolvedOneID,
                "isRedirect" => false,
            ])
            ->getBody();

        $mergedDiscussion = $this->api()
            ->get("/discussions/" . $unresolvedOneID)
            ->getBody();

        $this->assertSame($mergedDiscussion["internalStatusID"], RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED);
    }

    /**
     * Test that the "resolved" status of an unresolved discussion is not changed when another resolved discussion is merged into it.
     */
    public function testMergingResolvedIntoUnresolved(): void
    {
        $unresolved = $this->createDiscussion(["resolved" => false]);
        $unresolvedID = $unresolved["discussionID"];
        $resolved = $this->createDiscussion(["resolved" => true]);
        $resolvedID = $resolved["discussionID"];
        $this->api()->patch("/discussions/merge", [
            "discussionIDs" => [$resolvedID],
            "destinationDiscussionID" => $unresolvedID,
            "isRedirect" => false,
        ]);

        $mergedDiscussion = $this->api()
            ->get("/discussions/" . $unresolvedID)
            ->getBody();

        $this->assertSame($mergedDiscussion["internalStatusID"], RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED);
    }

    /**
     * Test that the "resolved" status of a resolved discussion is not changed when another resolved discussion is merged into it.
     */
    public function testMergingResolvedIntoResolved(): void
    {
        $resolvedOne = $this->createDiscussion(["resolved" => true]);
        $resolvedOneID = $resolvedOne["discussionID"];
        $resolvedTwo = $this->createDiscussion(["resolved" => true]);
        $resolvedTwoID = $resolvedTwo["discussionID"];

        $this->api()
            ->patch("/discussions/merge", [
                "discussionIDs" => [$resolvedTwoID],
                "destinationDiscussionID" => $resolvedOneID,
                "isRedirect" => false,
            ])
            ->getBody();

        $mergedDiscussion = $this->api()
            ->get("/discussions/" . $resolvedOneID)
            ->getBody();

        $this->assertSame($mergedDiscussion["internalStatusID"], RecordStatusModel::DISCUSSION_STATUS_RESOLVED);
    }

    /**
     * Test that the "resolved" status of a resolved discussion is not changed when another unresolved discussion is merged into it.
     */
    public function testMergingUnresolvedIntoResolved(): void
    {
        $unresolved = $this->createDiscussion(["resolved" => false]);
        $unresolvedID = $unresolved["discussionID"];
        $resolved = $this->createDiscussion(["resolved" => true]);
        $resolvedID = $resolved["discussionID"];
        $this->api()->patch("/discussions/merge", [
            "discussionIDs" => [$unresolvedID],
            "destinationDiscussionID" => $resolvedID,
            "isRedirect" => false,
        ]);

        $mergedDiscussion = $this->api()
            ->get("/discussions/" . $resolvedID)
            ->getBody();

        $this->assertSame($mergedDiscussion["internalStatusID"], RecordStatusModel::DISCUSSION_STATUS_RESOLVED);
    }

    /**
     * Test that the "resolved" status of a discussion is not affected by accepting an answer on a question.
     */
    public function testResolvedQnAInteraction(): void
    {
        // As member, ask a question.
        $member = $this->createUser();
        $this->api()->setUserID($member["userID"]);
        $question = $this->createQuestion();

        // As an admin, answer the question.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $answer = $this->createAnswer();

        // The question should be marked as resolved (because an admin responded) and answered.
        $updatedQuestion = $this->api()
            ->get("/discussions/{$question["discussionID"]}")
            ->getBody();
        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ANSWERED, $updatedQuestion["statusID"]);
        $this->assertSame(true, $updatedQuestion["resolved"]);
        $this->api()->setUserID($member["userID"]);

        // As the member, accept the answer.
        $this->api()->patch("/comments/answer/{$answer["commentID"]}", ["status" => "accepted"]);

        // The question's status should be changed to accepted, but its resolved status should not be affected.
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $updatedQuestion = $this->api()
            ->get("/discussions/{$question["discussionID"]}")
            ->getBody();
        $this->assertSame(\QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $updatedQuestion["statusID"]);
        $this->assertSame(true, $updatedQuestion["resolved"]);
    }

    /**
     * Test that the "resolved" status of a discussion is not affected by closing a discussion.
     */
    public function testResolvedClosedInteraction(): void
    {
        $discussion = $this->createDiscussion(["resolved" => true]);
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", ["closed" => true]);

        $updatedDiscussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();

        $this->assertSame(true, $updatedDiscussion["resolved"]);
        $this->assertSame(true, $updatedDiscussion["resolved"]);
    }

    /**
     * Test that the legacy resolved page redirects to triage.
     *
     * @return void
     */
    public function testLegacyPageRedirectsToTriage(): void
    {
        $this->assertRedirectsTo(url("/dashboard/content/triage", true), 302, function () {
            $this->bessy()->get("/discussions/unresolved");
        });
    }

    public function testResolveAll(): void
    {
        $communityMod = $this->createUser([
            "roleID" => [\RoleModel::MOD_ID],
        ]);

        $customRole = $this->createRole();
        $categoryMod = $this->createUser(["roleID" => [$customRole["roleID"], \RoleModel::MEMBER_ID]]);

        $cat1 = $this->createCategory();
        $disc1 = $this->createDiscussion();
        $permCat = $this->createPermissionedCategory(
            [],
            [$customRole["roleID"], \RoleModel::ADMIN_ID],
            [
                "posts.moderate" => [$customRole["roleID"]],
            ]
        );

        $disc2 = $this->createDiscussion();
        $disc3 = $this->createDiscussion();

        // Now if the category mod uses resolve all, it will only do the posts in his category.
        $this->assertDiscussionResolved($disc1, false);
        $this->assertDiscussionResolved($disc2, false);
        $this->assertDiscussionResolved($disc3, false);

        $this->runWithUser(function () {
            $this->api()->post("/discussions/resolve-bulk");
        }, $categoryMod);

        $this->assertDiscussionResolved($disc2);
        $this->assertDiscussionResolved($disc3);
        $this->assertDiscussionResolved($disc1, false);
    }
}
