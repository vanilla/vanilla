<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Akismet;

use Garden\Http\HttpResponse;
use Vanilla\Akismet\Clients\AkismetClient;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class MissedSpamTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $mockResponse = new HttpResponse(200, rawBody: "Thanks for making the web a better place.");
        $mockAkismetClient = $this->createMock(AkismetClient::class);
        $mockAkismetClient
            ->expects($this->any())
            ->method("submitSpam")
            ->with(
                $this->callback(function ($data) {
                    $this->assertArrayHasKey("comment_content", $data);
                    $this->assertEquals("My name\n\nsome post", $data["comment_content"]);
                    return true;
                })
            )
            ->willReturn($mockResponse);
        $this->container()->setInstance(AkismetClient::class, $mockAkismetClient);
    }

    /**
     * @inheritdoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "akismet";
        return $addons;
    }

    /**
     * Tests that posts not caught by akismet are sent to akismet when a report is rejected.
     *
     * @return void
     */
    public function testSubmitSpamOnRejectReport()
    {
        $category = $this->createCategory();
        $member = $this->createUserWithCategoryPermissions($category);

        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);

        $this->api()->post("/reports", [
            "recordID" => $discussion["discussionID"],
            "recordType" => "discussion",
            "reportReasonIDs" => ["spam"],
        ]);

        $report = $this->assertReportForRecord($discussion, [
            "recordIsLive" => true,
        ]);

        $this->api()->patch("/reports/{$report["reportID"]}/reject-record");
    }

    /**
     * Tests that posts not caught by akismet are sent to akismet when a report is escalated.
     *
     * @return void
     */
    public function testSubmitSpamOnEscalate()
    {
        $category = $this->createCategory();
        $member = $this->createUserWithCategoryPermissions($category);

        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);

        $this->createEscalation($discussion, ["reportReasonIDs" => [ReportReasonModel::INITIAL_REASON_SPAM]]);
    }

    /**
     * Tests that posts are sent to akismet when deleting spam in the legacy spam queue.
     *
     * @return void
     */
    public function testSubmitSpamOnDeleteSpam()
    {
        $member = $this->createUser();

        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion([
                "name" => "My name",
                "body" => "some post",
                "format" => TextFormat::FORMAT_KEY,
            ]);
        }, $member);
        $this->reactDiscussion($discussion, "Spam");

        $logModel = $this->container()->get(\LogModel::class);
        $logs = $logModel->getWhere(["recordType" => "discussion", "recordID" => $discussion["discussionID"]]);
        $this->assertCount(1, $logs);
        $logID = $logs[0]["LogID"];

        $this->bessy()->post("/log/DeleteSpam", [
            "LogIDs" => $logID,
        ]);
    }
}
