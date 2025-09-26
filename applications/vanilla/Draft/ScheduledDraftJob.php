<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Draft;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\ScheduledDraftModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Job to process scheduled drafts
 */
class ScheduledDraftJob extends LocalApiJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private ContentDraftModel $draftModel, private ScheduledDraftModel $draftScheduledModel)
    {
    }
    /**
     * @inheritdoc
     */
    public function setMessage(array $message)
    {
        // No message needed.
        return;
    }

    /**
     * Execute the currently active time based  jobs
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        if (!ContentDraftModel::draftSchedulingEnabled()) {
            $this->logger->info("Draft scheduling is not enabled. Skipping the job.");
            return JobExecutionStatus::abandoned();
        }
        $totalScheduledDrafts = $this->draftModel->getCurrentScheduledDraftsCount();
        if (!$totalScheduledDrafts) {
            $this->logger->info("No drafts are scheduled for publishing. Skipping the job.");
            return JobExecutionStatus::abandoned();
        }

        // If there is already a job in progress then do nothing
        if ($this->draftScheduledModel->isCurrentlyScheduled()) {
            $this->logger->info("There is already a job in progress. Skipping the job.");
            return JobExecutionStatus::abandoned();
        }

        try {
            $scheduledID = $this->draftScheduledModel->saveScheduled($totalScheduledDrafts, "scheduled_drafts");

            $trackingSlip = $this->draftModel->publishScheduledDraftsAction($scheduledID);

            //update the jobID in the scheduled table
            $this->draftScheduledModel->update(
                ["jobID" => $trackingSlip->getTrackingID()],
                ["scheduleID" => $scheduledID]
            );

            return JobExecutionStatus::complete();
        } catch (\Exception $e) {
            if (!empty($scheduledID)) {
                //drop the current Schedule as it failed.
                $this->draftScheduledModel->delete(["scheduleID" => $scheduledID]);
            }
            $this->logger->error("Error occurred while processing scheduled drafts.", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "scheduleID" => $scheduledID,
            ]);
            return JobExecutionStatus::failed();
        }
    }
    /**
     * Get the cron expression to run this job.
     *
     * @return string
     */
    public static function getCronExpression(): string
    {
        // Run every five minutes
        return "*/5 * * * *";
    }
}
