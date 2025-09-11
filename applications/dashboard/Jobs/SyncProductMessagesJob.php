<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Jobs;

use Vanilla\Dashboard\Models\ProductMessageModel;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Job to sync product messages.
 */
class SyncProductMessagesJob extends LocalApiJob
{
    /**
     * DI.
     */
    public function __construct(private ProductMessageModel $productMessageModel)
    {
    }

    /**
     * @inheritDoc
     */
    public function setMessage(array $message)
    {
        // Nothing to do.
    }

    /**
     * @inheritDoc
     */
    public function run(): JobExecutionStatus
    {
        try {
            $this->productMessageModel->syncAnnouncements();
        } catch (\Throwable $ex) {
            ErrorLogger::error($ex, ["productMessages"]);
            return JobExecutionStatus::error();
        }
        return JobExecutionStatus::success();
    }

    /**
     * Get the cron expression to run this job.
     *
     * @return string
     */
    public static function getCronExpression(): string
    {
        // Run every hour
        return "* */1 * * *";
    }
}
