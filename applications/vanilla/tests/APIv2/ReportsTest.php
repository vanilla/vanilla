<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers\Api;

use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for /api/v2/reports
 */
class ReportsTest extends SiteTestCase
{
    use CommunityApiTestTrait;
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
     * Test that we initial reports structure repeatedly.
     */
    public function testStructureTwice(): void
    {
        ReportReasonModel::structure(\Gdn::structure());
        $this->assertTrue(true);
    }

    /**
     * Test that we can create and read a report from the API.
     */
    public function testCreateAndReadReport(): void
    {
        $category = $this->createCategory(["name" => "My Category"]);
        CurrentTimeStamp::mockTime("2022-05-05");
        $discussion = $this->createDiscussion(["name" => "my discussion"]);
        $report = $this->createReport($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold*",
        ]);

        $this->assertDataLike(
            [
                "reportID" => 1,
                "insertUserID" => 2,
                "dateInserted" => "2022-05-05T00:00:00+00:00",
                "dateUpdated" => null,
                "updateUserID" => null,
                "status" => "new",
                "recordUserID" => 2,
                "recordType" => "discussion",
                "recordID" => 1,
                "placeRecordType" => "category",
                "placeRecordID" => $category["categoryID"],
                "recordUrl" => $discussion["url"],
                "placeRecordUrl" => $category["url"],
                "placeRecordName" => "My Category",
                "recordName" => "my discussion",
                "recordFormat" => "text",
                "noteHtml" => "<p><em>Bold</em></p>",
                "recordHtml" => "Hello Discussion",
                "reasons.0.name" => "Spam / Solicitation",
                "reasons.1.name" => "Abuse",
                "recordIsLive" => true,
                "recordWasEdited" => false,
            ],
            $report
        );

        // Check if the record was updated.
        CurrentTimeStamp::mockTime("2022-05-06");
        $this->api()->patch("/discussions/{$discussion["discussionID"]}", [
            "body" => "updated!",
        ]);

        $newReport = $this->api()
            ->get("/reports/{$report["reportID"]}")
            ->getBody();
        $this->assertDataLike(
            [
                "recordWasEdited" => true,
            ],
            $newReport
        );

        // Delete the report
        $this->api()->delete("/discussions/{$discussion["discussionID"]}");
        $newReport = $this->api()
            ->get("/reports/{$report["reportID"]}")
            ->getBody();
        $this->assertDataLike(
            [
                "recordWasEdited" => false,
                "recordIsLive" => false,
            ],
            $newReport
        );
    }

    /**
     * Test that we can list and filter reports.
     */
    public function testListAndFilterReports(): void
    {
        $this->resetTable("report");

        $cat1 = $this->createCategory(["name" => "Cat1"]);
        $discussion1 = $this->createDiscussion(["name" => "Discussion1"]);
        $report1 = $this->createReport($discussion1);
        $cat2 = $this->createCategory(["name" => "Cat2"]);
        $discussion2 = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment1Report = $this->createReport($comment1);
        $comment2 = $this->createComment();
        $comment2Report = $this->createReport($comment2);
        $cat3 = $this->createCategory(["name" => "Cat 3"]);
        $permDiscussion = $this->createDiscussion();
        $cat3Report = $this->createReport($permDiscussion);

        $cat1And2Moderator = $this->createCategoryMod([$cat1, $cat2]);
        $cat3Moderator = $this->createCategoryMod($cat3);

        $reports = $this->api()
            ->get("/reports")
            ->getBody();
        $this->assertCount(4, $reports);

        // I can filter to a specific category
        $reports = $this->api()
            ->get("/reports", [
                "placeRecordType" => "category",
                "placeRecordID" => [$cat1["categoryID"], $cat2["categoryID"]],
            ])
            ->getBody();
        $this->assertCount(3, $reports);

        // I can filter to a specific record
        $reports = $this->api()
            ->get("/reports", ["recordType" => "comment", "recordID" => $comment2["commentID"]])
            ->getBody();
        $this->assertCount(1, $reports);
        $this->assertEquals($comment2Report["reportID"], $reports[0]["reportID"]);

        $this->runWithUser(function () use ($cat3) {
            // If I don't have permission to see a report, I don't see the report.
            $reports = $this->api()
                ->get("/reports")
                ->getBody();
            $this->assertCount(3, $reports);
        }, $cat1And2Moderator);
    }

    /**
     * Test that we can filter by reporting user and record user.
     */
    public function testUserFilters()
    {
        $this->resetTable("report");
        $user1 = $this->createUser([
            "roleID" => [\RoleModel::MOD_ID, \RoleModel::MEMBER_ID],
        ]);
        $user2 = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID],
        ]);

        $this->createCategory();
        $user1Disc = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $user1);

        $user2Disc = $this->runWithUser(function () use ($user1Disc) {
            // Report post 1
            $this->createReport($user1Disc);

            return $this->createDiscussion();
        }, $user2);

        // Report post 2
        $this->runWithUser(function () use ($user2Disc) {
            $this->createReport($user2Disc);
        }, $user1);

        // We can filter by reporting user
        $reports = $this->api()
            ->get("/reports", ["insertUserID" => [$user2["userID"]]])
            ->getBody();
        $this->assertCount(1, $reports);
        $this->assertEquals($user1Disc["discussionID"], $reports[0]["recordID"]);

        // We can filter by reporting user role
        $reports = $this->api()
            ->get("/reports", ["insertUserRoleID" => [\RoleModel::MEMBER_ID]])
            ->getBody();
        $this->assertCount(2, $reports);
        $reports = $this->api()
            ->get("/reports", ["insertUserRoleID" => [\RoleModel::MOD_ID]])
            ->getBody();
        $this->assertCount(1, $reports);
        // User2 discussion was reported by user1 who is a moderator.
        $this->assertEquals($user2Disc["discussionID"], $reports[0]["recordID"]);

        // We can filter by record user
        $reports = $this->api()
            ->get("/reports", ["recordUserID" => [$user1["userID"]]])
            ->getBody();
        $this->assertCount(1, $reports);
        $this->assertEquals($user1Disc["discussionID"], $reports[0]["recordID"]);
    }

    /**
     * Test that we can filter by one or more reasons.
     */
    public function testFilterReasons()
    {
        $this->resetTable("report");
        $this->createCategory();
        $disc1 = $this->createDiscussion();

        $report1 = $this->createReport($disc1, ["reportReasonIDs" => ["spam"]]);
        $report2 = $this->createReport($disc1, ["reportReasonIDs" => ["spam", "abuse", "inappropriate"]]);
        $report3 = $this->createReport($disc1, ["reportReasonIDs" => ["abuse"]]);

        $reports = $this->api()
            ->get("/reports", [
                "reportReasonID" => ["spam", "inappropriate"],
            ])
            ->getBody();
        $this->assertCount(2, $reports);

        $this->assertRowsLike(
            [
                "reportID" => [$report1["reportID"], $report2["reportID"]],
            ],
            $reports
        );

        $reports = $this->api()
            ->get("/reports", [
                "reportReasonID" => "abuse,inappropriate",
            ])
            ->getBody();
        $this->assertCount(2, $reports);

        $this->assertRowsLike(
            [
                "reportID" => [$report2["reportID"], $report3["reportID"]],
            ],
            $reports
        );
    }

    /**
     * Test that we can create and get for automation from the API.
     */
    public function testReportAutomationEndpoint(): void
    {
        $this->createCategory(["name" => "My Category"]);
        CurrentTimeStamp::mockTime("2022-05-05");
        $discussion = $this->createDiscussion(["name" => "my discussion"]);
        $this->createReport($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold*",
        ]);
        $this->createReport($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold1*",
        ]);
        $this->createReport($discussion, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold2*",
        ]);

        $discussionNew = $this->createDiscussion(["name" => "my discussion"]);
        $this->createReport($discussionNew, [
            "reportReasonIDs" => ["spam", "abuse"],
            "noteBody" => "*Bold*",
        ]);

        $newReport = $this->api()
            ->get("/reports/automation", [
                "placeRecordType" => "category",
                "placeRecordID" => $this->lastInsertedCategoryID,
                "countReports" => 2,
            ])
            ->getBody();
        $this->assertCount(1, $newReport);
        $this->assertRowsLike(
            [
                "recordID" => [$discussion["discussionID"]],
                "recordType" => ["discussion"],
            ],
            $newReport
        );
    }
}
