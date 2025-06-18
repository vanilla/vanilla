<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Drafts;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Draft\ScheduledDraftService;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\ScheduledDraftModel;
use VanillaTests\Forum\ScheduledDraftTestTrait;
use VanillaTests\SiteTestCase;

class ScheduledDraftServiceTest extends SiteTestCase
{
    use ScheduledDraftTestTrait;

    private ScheduledDraftModel $scheduledDraftModel;
    private ScheduledDraftService $scheduledDraftService;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        self::enableFeature(ContentDraftModel::FEATURE);
        $structure = \Gdn::database()->structure();
        ContentDraftModel::structure($structure);
        ScheduledDraftModel::structure($structure);
    }
    public function setUp(): void
    {
        parent::setUp();
        $this->init();
        $this->resetTable("draftScheduled");
        $this->resetTable("contentDraft");
        $this->scheduledDraftModel = $this->container()->get(ScheduledDraftModel::class);
        $this->scheduledDraftService = $this->container()->get(ScheduledDraftService::class);
    }

    /**
     * Test that scheduled drafts are processed
     *
     * @return void
     * @throws \DateMalformedStringException
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function testScheduledDraftService()
    {
        $category = $this->createCategory(["name" => "Scheduled Drafts"]);
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
        CurrentTimeStamp::mockTime($currentTimeStamp);
        $record = $this->scheduleDraftRecord([
            "dateScheduled" => $currentTimeStamp->modify("+20 minutes")->format("c"),
        ]);

        // Create a draft
        $this->createScheduleDraft($record);
        CurrentTimeStamp::clearMockTime();

        // MAke sure currently there is no jobs to process
        $scheduledJobs = $this->scheduledDraftModel->select();
        $this->assertEmpty($scheduledJobs);

        // Make sure there is a record to process
        $count = $this->draftModel->getCurrentScheduledDraftsCount();
        $this->assertEquals(1, $count);

        // Test that the job is not executed if the flag is disabled
        $this->disableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $this->scheduledDraftService->processScheduledDrafts();

        // Make sure the draft is scheduled ans the job is executed
        $scheduledJobs = $this->scheduledDraftModel->select();
        $this->assertEmpty($scheduledJobs);

        $this->enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $this->scheduledDraftService->processScheduledDrafts();

        $scheduledJobs = $this->scheduledDraftModel->select();
        $this->assertCount(1, $scheduledJobs);
        $this->assertEquals(ScheduledDraftModel::PROCESSED, $scheduledJobs[0]["status"]);

        //Make sure the record is processed
        $count = $this->draftModel->getCurrentScheduledDraftsCount();
        $this->assertEquals(0, $count);
        $this->resetTable("draftScheduled");
    }
}
