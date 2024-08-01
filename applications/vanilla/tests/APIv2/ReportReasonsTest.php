<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\DatabaseTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for /api/v2/report-reasons
 */
class ReportReasonsTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use DatabaseTestTrait;

    /**
     * Test standard crud methods.
     */
    public function testCrud()
    {
        $reason1 = $this->createReportReason(["name" => "reason1", "description" => "awesome description"]);
        $this->assertEquals("reason1", $reason1["name"]);
        $this->assertEquals("awesome description", $reason1["description"]);

        // We can modify the reason
        $reason1 = $this->api()
            ->patch("/report-reasons/{$reason1["reportReasonID"]}", [
                "name" => "reason1.1",
                "description" => "awesome description 1.1",
            ])
            ->getBody();
        // now assert that the values were changed
        $this->assertEquals("reason1.1", $reason1["name"]);
        $this->assertEquals("awesome description 1.1", $reason1["description"]);

        // We can fetch a single reason as well.
        $reason = $this->api()
            ->get("/report-reasons/{$reason1["reportReasonID"]}")
            ->getBody();
        $this->assertEquals("reason1.1", $reason["name"]);

        // We can delete the reason
        $response = $this->api()->delete("/report-reasons/{$reason1["reportReasonID"]}");
        $this->assertEquals(204, $response->getStatusCode());

        // Reason is removed from the DB.
        $this->assertNoRecordsFound("reportReason", ["reportReasonID" => $reason1["reportReasonID"]]);
    }

    /**
     * Test access control on /api/v2/report-reasons
     */
    public function testReasonListPermissionFilters(): void
    {
        $member = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $mod = $this->createUser(["roleID" => [\RoleModel::MOD_ID]]);

        $reasons = $this->api()
            ->get("/report-reasons")
            ->getBody();
        // I have count reports as a community manager.
        $this->assertArrayHasKey("countReports", $reasons[0]);

        // I don't have count reports as regular user.
        $this->runWithUser(function () {
            $reasons = $this->api()
                ->get("/report-reasons")
                ->getBody();
            $this->assertArrayNotHasKey("countReports", $reasons[0]);
        }, $member);

        // Now we create a reason that only "mods" can see.
        $customReason = $this->createReportReason([
            "roleIDs" => [\RoleModel::MOD_ID],
        ]);

        // Community manager always has permission.
        $this->assertUserCanSeeReasons([$customReason], $this->api()->getUserID());

        // Mods can see the reason because they were given permission
        $this->assertUserCanSeeReasons([$customReason], $mod);

        // Member can't see the reason
        $this->assertUserCannotSeeReasons([$customReason], $member);

        // If you can't see the reason you get a 404 on the single GET so we don't expose that it's a valid reasonID.
        $this->runWithExpectedExceptionCode(404, function () use ($member, $customReason) {
            $this->runWithUser(function () use ($customReason) {
                $this->api()->get("/report-reasons/{$customReason["reportReasonID"]}");
            }, $member);
        });

        $this->createCategory();
        $discussion = $this->createDiscussion();
        // The user also can't make a report with that reason even if they know the name.
        $this->runWithExpectedExceptionCode(422, function () use ($member, $customReason, $discussion) {
            $this->runWithUser(function () use ($customReason, $discussion) {
                $this->createReport($discussion, ["reportReasonIDs" => [$customReason]]);
            }, $member);
        });
    }

    /**
     * @return void
     */
    public function testSoftDeleteReason(): void
    {
        $reason = $this->createReportReason();
        $this->createCategory();
        $post = $this->createDiscussion();

        $report = $this->createReport($post, [
            "reportReasonIDs" => [$reason],
        ]);

        // Now if we delete the report it is soft deleted.
        $this->api()->delete("/report-reasons/{$reason["reportReasonID"]}");
        $this->assertRecordsFound("reportReason", ["reportReasonID" => $reason["reportReasonID"], "deleted" => true]);

        $this->runWithExpectedExceptionCode(404, function () use ($reason) {
            $this->api()->get("/report-reasons/{$reason["reportReasonID"]}");
        });

        // But we can get it if we ask for deleted and we have community manage.
        $reasonResponse = $this->api()->get("/report-reasons/{$reason["reportReasonID"]}", ["includeDeleted" => true]);
        $this->assertEquals(200, $reasonResponse->getStatusCode());

        // We can't make a new report with this reason.
        $this->runWithExpectedExceptionCode(422, function () use ($post, $reason) {
            $this->createReport($post, ["reportReasonIDs" => [$reason]]);
        });

        // Our existing report still shows the reason

        $report = $this->api()
            ->get("/reports/{$report["reportID"]}")
            ->getBody();

        $this->assertEquals($reason["description"], $report["reasons"][0]["description"]);
        $this->assertTrue($report["reasons"][0]["deleted"]);

        // We still get a conflict response if the reasonID is duplicated.
        $this->runWithExpectedExceptionCode(409, function () use ($reason) {
            $this->createReportReason(["name" => $reason["name"], "reportReasonID" => $reason["reportReasonID"]]);
        });
    }

    /**
     * Test that we get count of reports using a particular reason.
     *
     * @return void
     */
    public function testReasonCounts(): void
    {
        $member = $this->createUser(["roleID" => [\RoleModel::MEMBER_ID]]);
        $this->createCategory();
        $post = $this->createDiscussion();

        $reason = $this->createReportReason([]);
        $report = $this->createReport($post, ["reportReasonIDs" => [$reason["reportReasonID"]]]);
        $reason = $this->api()->get("/report-reasons/{$reason["reportReasonID"]}");
        $this->assertEquals(1, $reason["countReports"]);
        $this->runWithUser(function () use ($reason) {
            $reasons = $this->api()
                ->get("/report-reasons/{$reason["reportReasonID"]}")
                ->getBody();
            $this->assertArrayNotHasKey("countReports", $reasons);
        }, $member);
    }

    /**
     * Test sorting of reasons.
     *
     * @return void
     */
    public function testSorts()
    {
        $this->resetTable("reportReason");
        $reason1 = $this->createReportReason(["reportReasonID" => "reason1"]);
        $reason2 = $this->createReportReason(["reportReasonID" => "reason2"]);
        $reason3 = $this->createReportReason(["reportReasonID" => "reason3"]);
        // Create a report with these reasons.
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $report = $this->createReport($discussion, [
            "reportReasonIDs" => [$reason1, $reason2, $reason3],
        ]);

        // Put them in reverse order.
        $this->api()->put("/report-reasons/sorts", [
            "reason3" => 0,
            "reason2" => 1,
            "reason1" => 2,
        ]);

        $reasons = $this->api()
            ->get("/report-reasons")
            ->getBody();
        $this->assertEquals(["reason3", "reason2", "reason1"], array_column($reasons, "reportReasonID"));

        // Now flip just 2 and 3.
        $this->api()->put("/report-reasons/sorts", [
            "reason3" => 1,
            "reason2" => 0,
        ]);

        $reasons = $this->api()
            ->get("/report-reasons")
            ->getBody();
        $expectedSort = ["reason2", "reason3", "reason1"];
        $this->assertEquals($expectedSort, array_column($reasons, "reportReasonID"));

        // Refetch the report and make sure it's sorted.
        $report = $this->api()
            ->get("/reports/{$report["reportID"]}")
            ->getBody();
        $this->assertEquals($expectedSort, array_column($report["reasons"], "reportReasonID"));
    }

    ///
    /// Utilities
    ///

    /**
     * Assert that a user can see certain reasons.
     *
     * @param array $expectedReasons
     * @param mixed $userOrUserID
     * @param bool $invert
     */
    private function assertUserCanSeeReasons(array $expectedReasons, mixed $userOrUserID, bool $invert = false): void
    {
        $this->runWithUser(function () use ($expectedReasons, $invert) {
            // Community manager always has permission.
            $reasons = $this->api()
                ->get("/report-reasons")
                ->getBody();
            $actualReasonIDs = array_column($reasons, "reportReasonID");

            foreach ($expectedReasons as $expectedReason) {
                $expectedReasonID = is_string($expectedReason) ? $expectedReason : $expectedReason["reportReasonID"];

                if ($invert) {
                    $this->assertNotContains(
                        $expectedReasonID,
                        $actualReasonIDs,
                        "Expected user NOT to be able to see reasonID."
                    );
                } else {
                    $this->assertContains(
                        $expectedReasonID,
                        $actualReasonIDs,
                        "Expected user to be able to see reasonID."
                    );
                }
            }
        }, $userOrUserID);
    }

    /**
     * Assert that a user can't see certain reasons.
     *
     * @param array $expectedReasons
     * @param mixed $userOrUserID
     */
    private function assertUserCannotSeeReasons(array $expectedReasons, mixed $userOrUserID): void
    {
        $this->assertUserCanSeeReasons($expectedReasons, $userOrUserID, true);
    }
}
