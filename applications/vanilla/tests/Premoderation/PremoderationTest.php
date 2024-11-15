<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Premoderation\ApprovalPremoderator;
use Vanilla\Premoderation\PremoderationHandlerInterface;
use Vanilla\Premoderation\PremoderationItem;
use Vanilla\Premoderation\PremoderationResponse;
use Vanilla\Premoderation\PremoderationService;
use VanillaTests\AuditLogTestTrait;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\ExpectedAuditLog;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the new premoderation system used with community management.
 */
class PremoderationTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use AuditLogTestTrait;
    use DatabaseTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableFeature("escalations");
        $premodService = $this->container()->get(PremoderationService::class);

        // Apply our standard approval handler
        $premodService->clearHandlers();
        $premodService->registerHandler($this->container()->get(ApprovalPremoderator::class));
    }

    /**
     * Test basic premoderation
     *
     * @return void
     */
    public function testBasic(): void
    {
        $moderationUser = $this->createUser(["name" => "MyAutoMod"]);
        $this->mockPremoderationResponse(
            new PremoderationResponse(PremoderationResponse::SPAM, $moderationUser["userID"])
        );

        $memberUser = $this->createUser([]);

        $premodDiscussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $memberUser);
        // This went to premoderation
        $this->assertEquals(202, $premodDiscussion["status"]);
        $expectedFields = [
            "recordUserID" => $memberUser["userID"],
            "recordIsLive" => false,
            "recordType" => "discussion",
            "recordID" => null, // No recordID because the post was never actually made.
        ];
        $this->assertReportForRecord(
            $premodDiscussion,
            $expectedFields + ["reasons.0.reportReasonID" => ReportReasonModel::INITIAL_REASON_SPAM_AUTOMATION]
        );
    }

    /**
     * Test a workflow involving:
     * - Create a record
     * - Record goes to premoderation
     * - Record is approved from premoderation and restored
     * - User is then marked as verified
     * - Record is rejected and removed.
     *
     * @return void
     */
    public function testApproveAndReject(): void
    {
        $startDate = CurrentTimeStamp::mockTime("2024-01-01");
        $cat = $this->createCategory();
        $catMod = $this->createCategoryMod($cat);
        $member = $this->createUserWithCategoryPermissions($cat);
        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::APPROVAL_REQUIRED, null));
        $premodItem = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);
        $report = $this->assertReportForRecord($premodItem);

        $approveDate = CurrentTimeStamp::mockTime("2024-02-01");
        $this->runWithUser(function () use ($report) {
            $this->api()->patch("/reports/{$report["reportID"]}/approve-record");
        }, $catMod);
        $report = $this->assertReportForRecord($premodItem, [
            "status" => ReportModel::STATUS_DISMISSED,
            "recordIsLive" => true,
        ]);
        $this->assertNotNull($report["recordID"], "Expected recordID to be set after approval.");
        // And the discussion should exist now.
        $discussion = $this->api()
            ->get("/discussions/{$report["recordID"]}", ["expand" => "attachments"])
            ->getBody();
        // The discussion should be created.
        $this->assertDataLike(
            [
                "name" => "My name",
                "body" => "some post",
                // The post is forward-dated to the approval date.
                "dateInserted" => CurrentTimeStamp::getDateTime()->format(\DateTimeInterface::RFC3339),
                // The post is inserted from the original user
                "insertUserID" => $member["userID"],
            ],
            $discussion
        );
        $this->assertEquals(false, $member["bypassSpam"]);

        // We can call approve again without breaking and use the optional flag to verify the user.
        $this->api()->patch("/reports/{$report["reportID"]}/approve-record", ["verifyRecordUser" => true]);
        $member = $this->api()
            ->get("/users/{$member["userID"]}")
            ->getBody();
        $this->assertEquals(true, $member["bypassSpam"]);

        // We can go back and reject this post and it will create a log entry
        $this->runWithUser(function () use ($report) {
            $this->api()->patch("/reports/{$report["reportID"]}/reject-record");
        }, $catMod);
        $report = $this->assertReportForRecord($premodItem, [
            "status" => ReportModel::STATUS_REJECTED,
            "recordIsLive" => false,
        ]);
        $this->runWithExpectedExceptionCode(410, function () use ($report) {
            $this->api()->get("/discussions/{$report["recordID"]}");
        });
    }

    /**
     * Test a workflow involving a post being premoderated then rejected.
     */
    public function testReject(): void
    {
        $cat = $this->createCategory();
        $catMod = $this->createCategoryMod($cat);
        $member = $this->createUserWithCategoryPermissions($cat);
        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::APPROVAL_REQUIRED, null));
        $premodItem = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);
        $report = $this->assertReportForRecord($premodItem, [
            "recordIsLive" => false,
        ]);

        $this->runWithUser(function () use ($report) {
            $this->api()->patch("/reports/{$report["reportID"]}/reject-record");
        }, $catMod);

        // Record has been rejected and is totally gone.
        $this->assertEquals(
            [],
            $this->api()
                ->get("/discussions", ["categoryID" => $cat["categoryID"]])
                ->getBody(),
            "There should be no discussions"
        );
        $this->assertReportForRecord($premodItem, [
            "status" => ReportModel::STATUS_REJECTED,
            "recordIsLive" => false,
        ]);
    }

    /**
     * Test that super spam is never escalated.
     *
     * @return void
     */
    public function testSuperSpam(): void
    {
        $modUser = $this->createUser();
        $this->mockPremoderationResponse(
            new PremoderationResponse(PremoderationResponse::SUPER_SPAM, $modUser["userID"])
        );
        $member = $this->createUser(["name" => "spammer"]);
        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $member);
        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());

        $this->assertAuditLogged(
            ExpectedAuditLog::create("premoderation_superSpam")->withMessage(
                "Rejected `discussion` by `spammer` as super spam."
            )
        );
    }

    /**
     * Test that a post that went through properly originally is still subject to premoderation on edit.
     *
     * @return void
     */
    public function testEditForOriginallyValidPost(): void
    {
        $cat = $this->createCategory();
        $catMod = $this->createCategoryMod($cat);
        $member = $this->createUserWithCategoryPermissions($cat);

        // Member creates a post
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);

        $this->assertEquals(201, $this->lastCommunityResponse->getStatusCode());

        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::SPAM, null));

        CurrentTimeStamp::mockTime("2024-03-01");

        // Edit came through and gets premoderated
        $this->runWithUser(function () use ($discussion) {
            $this->api()->patch("/discussions/{$discussion["discussionID"]}", ["name" => "New name"]);
        }, $member);

        CurrentTimeStamp::mockTime("2024-04-01");

        $report = $this->assertReportForRecord($discussion, [
            "recordIsLive" => true, // Record is live but an update is pending
            "reasons.0.reportReasonID" => ReportReasonModel::INITIAL_REASON_SPAM_AUTOMATION,
            // The report is marked as being for a pending update.
            "isPendingUpdate" => true,
        ]);

        // We can go through and approve the edit
        $this->runWithUser(function () use ($report) {
            $this->api()->patch("/reports/{$report["reportID"]}/approve-record");
        }, $catMod);

        $discussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertEquals("New name", $discussion["name"]);
        $this->assertEquals(
            CurrentTimeStamp::getDateTime()->format(\DateTimeInterface::RFC3339),
            $discussion["dateUpdated"]
        );
    }

    /**
     * Test a workflow involving a post being premoderated then approved.
     * The comment is then updated causing the escalation to be re-opened.
     * That is then approved and the comment is updated.
     *
     * @return void
     */
    public function testEditsForOriginallyPremoderatedPost(): void
    {
        CurrentTimeStamp::mockTime("2024-01-01");
        $cat = $this->createCategory();
        $catMod = $this->createCategoryMod($cat);
        $member = $this->createUserWithCategoryPermissions($cat);

        // Member creates a post and gets premoderated
        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::SPAM, null));
        $discussion = $this->createDiscussion([
            "name" => "comment parent",
        ]);
        $comment = $this->runWithUser(function () {
            return $this->createComment([
                "body" => "some comment",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);

        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());
        $report = $this->assertReportForRecord($comment);

        // Now we approve it
        $this->api()->patch("/reports/{$report["reportID"]}/approve-record");
        $report = $this->assertReportForRecord($comment, [
            "recordName" => "Re: comment parent",
            "recordIsLive" => true,
            "status" => ReportModel::STATUS_DISMISSED, // We are finished.
        ]);
        $commentID = $report["recordID"];

        // Now the user makes 2 edits in a row.
        CurrentTimeStamp::mockTime("2024-02-01");
        $response = $this->runWithUser(function () use ($commentID) {
            return $this->api()->patch("/comments/{$commentID}", [
                "body" => "comment update",
            ]);
        }, $member);
        $this->assertEquals(202, $response->getStatusCode());
        $report2ID = $response->getBody()["reportID"];
        $response = $this->runWithUser(function () use ($commentID) {
            return $this->api()->patch("/comments/{$commentID}", [
                "body" => "comment update 2",
            ]);
        }, $member);
        $this->assertEquals(202, $response->getStatusCode());
        $report3ID = $response->getBody()["reportID"];

        $reports = $this->api()
            ->get("/reports", ["recordType" => "comment", "recordID" => $commentID])
            ->getBody();
        $this->assertCount(2, $reports, "One of the results was deduplicated.");

        // Report 2 was removed
        $this->runWithExpectedExceptionCode(404, function () use ($report2ID) {
            $this->api()->get("/reports/{$report2ID}");
        });
    }

    /**
     * Test that global and category mods bypass premoderation.
     *
     * @return void
     */
    public function testPremoderationBypasses(): void
    {
        $cat = $this->createCategory();
        $globalMod = $this->createGlobalMod();
        // Global Mod bypasses all.
        $this->runWithUser(function () {
            $this->assertByPassesPremoderation(function () {
                $this->createDiscussion();
                $this->createComment();
            });
        }, $globalMod);
        // Category mod bypasses all for category.
        $catMod = $this->createCategoryMod($cat);
        $this->runWithUser(function () use ($cat) {
            $this->assertByPassesPremoderation(function () use ($cat) {
                $this->createDiscussion(["categoryID" => $cat["categoryID"]]);
                $this->createComment(["categoryID" => $cat["categoryID"]]);
            });
        }, $catMod);
    }

    /**
     * Test that category mods only bypass premoderation in their own category.
     *
     * @return void
     */
    public function testCategoryModIsPremoderatedOnOtherCategories(): void
    {
        $cat1 = $this->createCategory();
        $cat2 = $this->createCategory();
        $cat2Mod = $this->createCategoryMod($cat2);
        $this->runWithUser(function () use ($cat2) {
            $this->assertByPassesPremoderation(function () use ($cat2) {
                $this->createDiscussion(["categoryID" => $cat2["categoryID"]]);
            });
        }, $cat2Mod);

        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::APPROVAL_REQUIRED, null));
        $result = $this->runWithUser(function () use ($cat1) {
            return $this->createDiscussion(["categoryID" => $cat1["categoryID"]]);
        }, $cat2Mod);
        $this->assertEquals(
            202,
            $this->lastCommunityResponse->getStatusCode(),
            "Expected user to be premoderated but they weren't."
        );
    }

    /**
     * Test the global approval requirements and verification.
     */
    public function testGlobalApprovalPremoderation(): void
    {
        $roleNeedsApproval = $this->createRole(
            [],
            [
                "approval.require" => true,
            ]
        );
        $memberNeedsApproval = $this->createUser([
            "roleID" => [$roleNeedsApproval["roleID"], \RoleModel::MEMBER_ID],
        ]);
        $post = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $memberNeedsApproval);
        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());

        // Now let's verify the user
        $this->api()->patch("/reports/{$post["reportID"]}/approve-record", ["verifyRecordUser" => true]);

        // Now the user doesn't get premoderated
        $post = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $memberNeedsApproval);
        $this->assertEquals(201, $this->lastCommunityResponse->getStatusCode());
    }

    /**
     * Test category specific approval requirements and verification.
     */
    public function testCategoryApprovalRequired()
    {
        $member = $this->createUser(
            [],
            [
                "Verified" => true,
            ]
        );

        $category = $this->createCategory();
        $this->setConfigs([
            ApprovalPremoderator::CONF_PREMODERATED_CATEGORY_IDS => [$category["categoryID"]],
            ApprovalPremoderator::CONF_PREMODERATED_COMMENTS_ENABLED => false,
            ApprovalPremoderator::CONF_PREMODERATED_DISCUSSIONS_ENABLED => true,
        ]);

        // User hits premoderation on this category for discussions.
        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $member);
        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());

        // Not for comments though.
        $discussion = $this->createDiscussion();
        $this->runWithUser(function () use ($discussion) {
            $this->createComment(["discussionID" => $discussion["discussionID"]]);
        }, $member);
        $this->assertEquals(201, $this->lastCommunityResponse->getStatusCode());
    }

    /**
     * Test that keyword based moderation works.
     */
    public function testKeywordApprovalRequirement(): void
    {
        $member = $this->createUser(
            [],
            [
                "Verified" => true,
            ]
        );

        $this->setConfigs([
            ApprovalPremoderator::CONF_PREMODERATED_KEYWORDS => "naughty;ugly",
        ]);

        $this->runWithUser(function () {
            $this->createDiscussion(["name" => "very naughty"]);
        }, $member);
        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());

        $this->runWithUser(function () {
            $this->createDiscussion(["body" => "ugly"]);
        }, $member);
        $this->assertEquals(202, $this->lastCommunityResponse->getStatusCode());
    }

    /**
     * Test that the correct message is shown when a comment posted through the legacy controller is premoderated.
     */
    public function testCommentSpam(): void
    {
        $this->api()->setUserID(self::$siteInfo["adminUserID"]);
        $this->createCategory();
        $discussion = $this->createDiscussion();

        $comment = $this->createComment();
        $member = $this->createUser();
        $this->mockPremoderationResponse(new PremoderationResponse(PremoderationResponse::SPAM, null));
        $this->runWithUser(function () use ($discussion, $comment) {
            $response = $this->bessy()
                ->post("/post/comment/?discussionid={$discussion["discussionID"]}", [
                    "DiscussionID" => $discussion["discussionID"],
                    "Format" => "Wysiwyg",
                    "Body" => "test comment",
                    "Type" => "Post",
                    "LastCommentID" => $comment["commentID"],
                ])
                ->getInformMessages();
            $this->assertSame("Your comment will appear after it is approved.", $response[0]["Message"]);
        }, $member);
    }

    /**
     * Ensure the premoderation service returns the expected response.
     *
     * @param PremoderationResponse $expectedResponse
     */
    private function mockPremoderationResponse(PremoderationResponse $expectedResponse): void
    {
        $service = $this->container()->get(PremoderationService::class);

        $service->clearHandlers();
        $service->registerHandler(
            new class ($expectedResponse) implements PremoderationHandlerInterface {
                public function __construct(private PremoderationResponse $expectedResponse)
                {
                }

                public function premoderateItem(PremoderationItem $item): PremoderationResponse
                {
                    return $this->expectedResponse;
                }
            }
        );
    }

    /**
     * @param callable $callable
     * @return void
     */
    private function assertByPassesPremoderation(callable $callable): void
    {
        $service = $this->container()->get(PremoderationService::class);
        $service->clearHandlers();

        $handler = new class implements PremoderationHandlerInterface {
            public function __construct(public bool $didExecute = false)
            {
            }

            public function premoderateItem(PremoderationItem $item): PremoderationResponse
            {
                $this->didExecute = true;
                return PremoderationResponse::valid();
            }
        };
        $service->registerHandler($handler);
        call_user_func($callable);
        $this->assertEquals(
            false,
            $handler->didExecute,
            "Expected user to bypass premoderation but instead the premoderation handler executed."
        );
    }
}
