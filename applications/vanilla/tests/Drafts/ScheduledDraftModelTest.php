<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Drafts;

use Vanilla\CurrentTimeStamp;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\ScheduledDraftModel;
use VanillaTests\SiteTestCase;

/**
 * Test the DraftScheduledModel
 */
class ScheduledDraftModelTest extends SiteTestCase
{
    protected ScheduledDraftModel $scheduledDraftModel;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $database = new \Gdn_Database();
        ScheduledDraftModel::structure($database->structure());
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->scheduledDraftModel = $this->container()->get(ScheduledDraftModel::class);
    }

    /**
     * Test if a draft job is currently scheduled
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function testIsCurrentlyScheduled()
    {
        $this->assertFalse($this->scheduledDraftModel->isCurrentlyScheduled());
        for ($i = 0; $i < 2; $i++) {
            $this->scheduledDraftModel->saveScheduled($i + 5, "test-job-id-" . $i);
        }
        $this->assertTrue($this->scheduledDraftModel->isCurrentlyScheduled());
    }

    /**
     * Test old records are deleted
     *
     * @return void
     * @throws \Exception
     */
    public function testPrune()
    {
        CurrentTimeStamp::mockTime("2025-01-01 10:00:00");
        $this->resetTable($this->scheduledDraftModel->getTable());
        for ($i = 0; $i < 15; $i++) {
            $this->scheduledDraftModel->saveScheduled($i * 5, "test-job-id-" . $i);
        }
        $scheduledDrafts = $this->scheduledDraftModel->select(
            ["status" => ScheduledDraftModel::SCHEDULED],
            ["select" => ["count(*) as scheduled"]]
        );
        $this->assertEquals(15, $scheduledDrafts[0]["scheduled"]);
        CurrentTimeStamp::clearMockTime();
        $this->scheduledDraftModel->saveScheduled("100", "last-job-id-" . $i);
        $scheduledDrafts = $this->scheduledDraftModel->select(
            ["status" => ScheduledDraftModel::SCHEDULED],
            ["select" => ["count(*) as scheduled"]]
        );
        $this->assertEquals(6, $scheduledDrafts[0]["scheduled"]);
    }

    /**
     * Test update status of a scheduled job
     *
     * @return void
     */
    public function testUpdateStatus()
    {
        $scheduleID = $this->scheduledDraftModel->saveScheduled(5, "test-job-id");
        $this->scheduledDraftModel->updateStatus($scheduleID, ScheduledDraftModel::PROCESSING);
        $result = $this->scheduledDraftModel->selectSingle(["scheduleID" => $scheduleID], ["select" => "status"]);
        $this->assertEquals(ScheduledDraftModel::PROCESSING, $result["status"]);
        $this->assertTrue($this->scheduledDraftModel->updateStatus($scheduleID, ScheduledDraftModel::PROCESSED));

        $result = $this->scheduledDraftModel->selectSingle(["scheduleID" => $scheduleID], ["select" => "status"]);
        $this->assertEquals(ScheduledDraftModel::PROCESSED, $result["status"]);

        $this->expectException(\InvalidArgumentException::class);
        $this->scheduledDraftModel->updateStatus($scheduleID, "invalid");
    }
}
