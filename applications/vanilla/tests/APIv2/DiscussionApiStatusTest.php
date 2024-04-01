<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\Dashboard\Models\RecordStatusModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for statuses on the discussions api controller.
 */
class DiscussionApiStatusTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["QnA"];

    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /** @var int */
    private $internalStatusID;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
        $this->recordStatusModel = static::container()->get(RecordStatusModel::class);
    }

    /**
     * Test PUT /discussions/:id/status
     *
     * @param int $statusID New status ID of the
     * @param string $note String Reason for status change.
     * @param bool $exception Excepting exception.
     * @dataProvider discussionStatusUpdates
     */
    public function testPutDiscussionsStatus(int $statusID, string $note, bool $exception = false): void
    {
        $discussion = $this->createDiscussion();
        $id = $discussion["discussionID"];
        if ($exception) {
            $this->expectException(ClientException::class);
        }
        $newStatusDiscussion = $this->api()
            ->put("/discussions/{$id}/status", ["statusID" => $statusID, "statusNotes" => $note])
            ->getBody();
        $this->assertSame($statusID, $newStatusDiscussion["statusID"]);
    }

    /**
     * Data provider for testUpdateDiscussionStatus method
     * @return array
     */
    public function discussionStatusUpdates(): array
    {
        $result = [
            "change Status to StatusID 2" => [2, "some reason"],
            "change Status to StatusID 1" => [1, "Another some reason"],
            "change Status to StatusID 3" => [3, "Another some reason"],
            "change Status to StatusID 4" => [4, "Another some reason4"],
            "change Status to StatusID 5" => [5, "Another some reason5", true],
        ];

        return $result;
    }

    /**
     * Test GET /discussions/{id}/status-log
     * @depends testDiscussionStatusLogSave
     * @param  array $discussionStatusData DiscussionID to find log for
     */
    public function testGetDiscussionStatusLog(array $discussionStatusData): void
    {
        $id = array_keys($discussionStatusData)[0];
        $actualData = $discussionStatusData[$id];

        $results = $this->api()
            ->get("/discussions/{$id}/status-log", ["limit" => 10, "page" => 1])
            ->getBody();

        foreach ($results as $key => $result) {
            $this->assertEquals($id, $result["recordID"]);
            $this->assertEquals($actualData[$key]["statusID"], $result["statusID"]);
            $this->assertEquals($actualData[$key]["reason"], $result["reason"]);
        }
    }

    /**
     * Test Discussion StatusLogSave
     *
     */
    public function testDiscussionStatusLogSave(): array
    {
        $this->internalStatusID = $this->recordStatusModel->insert([
            "Name" => "Internal Status",
            "State" => "open",
            "RecordType" => "discussion",
            "RecordSubtype" => "discussion",
            "IsDefault" => 0,
            "isActive" => 1,
            "isInternal" => 1,
        ]);
        $discussionID = $this->createDiscussion()["discussionID"];
        $discussionUpdates[$discussionID] = [];
        $externalDiscussionUpdates[$discussionID] = [];
        $statusUpdateData = $this->discussionStatusUpdates();
        $statusUpdateData["change Status to StatusID " . $this->internalStatusID] = [
            $this->internalStatusID,
            "Another some reason " . $this->internalStatusID,
            true,
        ];
        unset($statusUpdateData["change Status to StatusID 5"]);
        $now = time();
        CurrentTimeStamp::mockTime($now);

        foreach ($statusUpdateData as $updateData) {
            $newStatusDiscussion = $this->api()
                ->put("/discussions/{$discussionID}/status", [
                    "statusID" => $updateData[0],
                    "statusNotes" => $updateData[1],
                ])
                ->getBody();
            $discussionUpdates[$discussionID][] = [
                "statusID" =>
                    count($updateData) == 2
                        ? $newStatusDiscussion["statusID"]
                        : $newStatusDiscussion["internalStatusID"],
                "reason" => $updateData[1],
            ];
            if (count($updateData) == 2) {
                $externalDiscussionUpdates[$discussionID][] = [
                    "statusID" => $newStatusDiscussion["statusID"],
                    "reason" => $updateData[1],
                ];
            }
            CurrentTimeStamp::mockTime(++$now);
        }
        $recordStatusLogModel = self::container()->get(RecordStatusLogModel::class);

        $recordStatusLogData = $recordStatusLogModel->getRecordStatusLogCount($discussionID);
        $this->assertSame(count($discussionUpdates[$discussionID]), $recordStatusLogData);
        // Check user without permissions will not see internal comments.
        $user = $this->createUser();
        $this->runWithUser(function () use ($recordStatusLogModel, $discussionID, $externalDiscussionUpdates) {
            $records = $recordStatusLogModel->getAllowedStatusLogs(
                ["recordID" => $discussionID, "recordType" => "discussion"],
                ["orderFields" => ["recordLogID"], "select" => ["statusID", "reason"]]
            );
            $this->assertEquals($externalDiscussionUpdates[$discussionID], $records);
        }, $user);

        $records = $recordStatusLogModel->getAllowedStatusLogs(
            ["recordID" => $discussionID, "recordType" => "discussion"],
            ["orderFields" => ["recordLogID"], "select" => ["statusID", "reason"]]
        );
        $this->assertEquals($discussionUpdates[$discussionID], $records);
        return $discussionUpdates;
    }

    /**
     * Test 'expandDiscussionsStatuses()' method with array of discussions.
     * @param  array $discussionStatusData DiscussionID to find log for
     *
     * @depends testDiscussionStatusLogSave
     */
    public function testExpandStatusLog(array $discussionStatusData): void
    {
        // Check a single discussion
        $discussionID = array_keys($discussionStatusData)[0];
        $discussion = $this->api()->get("/discussions/{$discussionID}", ["expand" => "status.log"]);

        $this->assertEquals(\QnAPlugin::DISCUSSION_STATUS_REJECTED, $discussion["statusID"]);
        $this->assertEquals(\QnAPlugin::DISCUSSION_STATUS_REJECTED, $discussion["status"]["statusID"]);
        $this->assertEquals("Another some reason4", $discussion["status"]["log"]["reasonUpdated"]);
        $this->assertEquals(\Gdn::session()->User->Name, $discussion["status"]["log"]["updateUser"]["name"]);
        $this->assertEquals("Another some reason 10000", $discussion["internalStatus"]["log"]["reasonUpdated"]);
        $this->assertEquals(\Gdn::session()->User->Name, $discussion["internalStatus"]["log"]["updateUser"]["name"]);
    }

    /**
     * Test that inactive statuses are replaced by the default one.
     */
    public function testExpandInactiveStatusLog(): void
    {
        $discussion = $this->createDiscussion();

        $statusID = $this->recordStatusModel->insert([
            "Name" => __FUNCTION__,
            "State" => "open",
            "RecordType" => "discussion",
            "RecordSubtype" => "discussion",
            "IsDefault" => 0,
            "isActive" => 0,
            "isInternal" => 0,
        ]);
        $this->api()->put("/discussions/{$discussion["discussionID"]}/status", ["statusID" => $statusID]);

        $internalStatusID = $this->recordStatusModel->insert([
            "Name" => "internal" . __FUNCTION__,
            "State" => "open",
            "RecordType" => "discussion",
            "RecordSubtype" => "discussion",
            "IsDefault" => 0,
            "isActive" => 0,
            "isInternal" => 1,
        ]);
        $this->api()->put("/discussions/{$discussion["discussionID"]}/status", ["statusID" => $internalStatusID]);

        $result = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "status.log"])
            ->getBody();
        $this->assertEquals(RecordStatusModel::DISCUSSION_STATUS_NONE, $result["status"]["statusID"]);
        $this->assertEquals(RecordStatusModel::DISCUSSION_INTERNAL_STATUS_NONE, $result["internalStatus"]["statusID"]);
    }

    /**
     * Test recordStatusModel's expandStatuses() redaction process.
     */
    public function testExpandRedactedDiscussionsStatus(): void
    {
        // Create a new recordStatus.
        $newStatusName = "My new status";
        $newStatusID = $this->recordStatusModel->insert([
            "Name" => $newStatusName,
            "State" => "open",
            "RecordType" => "discussion",
            "RecordSubtype" => "discussion",
            "IsDefault" => 0,
            "isActive" => 1,
        ]);

        $discussion = $this->createDiscussion([], ["statusID" => $newStatusID]);

        // Fetch with expanded status.
        $discussion = $this->api()->get("/discussions/{$discussion["discussionID"]}?expand=status,status.log");

        // Assert that the recordStatus data is unchanged.
        $this->assertEquals($newStatusID, $discussion["statusID"]);
        $this->assertEquals($newStatusID, $discussion["status"]["statusID"]);
        $this->assertEquals($newStatusName, $discussion["status"]["name"]);

        // Set recordStatus `isActive` value to 0.
        $this->recordStatusModel->setIsActive($newStatusID, false);

        // Try to expand the status again.
        $discussion = $this->api()->get("/discussions/{$discussion["discussionID"]}?expand=status,status.log");
        // Assert that the recordStatus data has been redacted.
        $this->assertEquals(0, $discussion["statusID"]);
        $this->assertEquals(0, $discussion["status"]["statusID"]);
        $this->assertEquals("None", $discussion["status"]["name"]);
    }
}
