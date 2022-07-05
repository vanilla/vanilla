<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\Dashboard\Models\RecordStatusModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for statuses on the discussions api controller.
 */
class DiscussionApiStatusTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    public static $addons = ["QnA"];

    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->discussionModel = self::container()->get(\DiscussionModel::class);
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
     */
    public function testDiscussionStatusLogSave(): array
    {
        $discussionID = $this->createDiscussion()["discussionID"];
        $discussionUpdates[$discussionID] = [];
        $statusUpdateData = $this->discussionStatusUpdates();
        unset($statusUpdateData["change Status to StatusID 5"]);
        foreach ($statusUpdateData as $updateData) {
            $newStatusDiscussion = $this->api()
                ->put("/discussions/{$discussionID}/status", [
                    "statusID" => $updateData[0],
                    "statusNotes" => $updateData[1],
                ])
                ->getBody();
            $discussionUpdates[$discussionID][] = [
                "statusID" => $newStatusDiscussion["statusID"],
                "reason" => $updateData[1],
            ];
        }
        $recordStatusLogModel = self::container()->get(RecordStatusLogModel::class);

        $recordStatusLogData = $recordStatusLogModel->getRecordStatusLogCount($discussionID);
        $this->assertSame(count($discussionUpdates[$discussionID]), $recordStatusLogData);
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
        $discussion = $this->api()->get("/discussions/{$discussionID}", ["expand" => "status,status.log"]);

        $this->assertEquals(\QnAPlugin::DISCUSSION_STATUS_REJECTED, $discussion["statusID"]);
        $this->assertEquals(\QnAPlugin::DISCUSSION_STATUS_REJECTED, $discussion["status"]["statusID"]);
        $this->assertEquals("Another some reason4", $discussion["status"]["log"]["reasonUpdated"]);
        $this->assertEquals(\Gdn::session()->User->Name, $discussion["status"]["log"]["updateUser"]["name"]);
    }

    /**
     * Test recordStatusModel's expandStatuses() redaction process.
     */
    public function testExpandRedactedDiscussionsStatus(): void
    {
        // Create a new recordStatus.
        /** @var RecordStatusModel $recordStatusModel */
        $recordStatusModel = self::container()->get(RecordStatusModel::class);
        $newStatusName = "My new status";
        $newStatusID = $recordStatusModel->insert([
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
        $recordStatusModel->setIsActive($newStatusID, false);

        // Try to expand the status again.
        $discussion = $this->api()->get("/discussions/{$discussion["discussionID"]}?expand=status,status.log");
        // Assert that the recordStatus data has been redacted.
        $this->assertEquals(0, $discussion["statusID"]);
        $this->assertEquals(0, $discussion["status"]["statusID"]);
        $this->assertEquals("None", $discussion["status"]["name"]);
    }
}
