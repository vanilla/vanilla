<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Dashboard\AutomationRules\Jobs;

use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\CurrentTimeStamp;
use Vanilla\Logger;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;
use Exception;

/**
 * Cron Job to execute time based automation rules at specific intervals
 */
class AutomationRuleJob extends LocalApiJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private AutomationRuleModel $automationRuleModel;

    public function __construct(AutomationRuleModel $automationRuleModel)
    {
        $this->automationRuleModel = $automationRuleModel;
    }

    /**
     * @inheridoc
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
        $jobID = uniqid("ar_");
        $activeTimedAutomationRuleIDs = $this->automationRuleModel->getActiveTimedAutomationRuleIDs();
        $error = false;
        $this->logger->info("Starting timed automation hourly job.", [
            Logger::FIELD_TAGS => ["automationRules", "automationRuleJob"],
            "DateTime" => CurrentTimeStamp::getDateTime(),
            "ActiveTimedAutomationRuleIDs" => $activeTimedAutomationRuleIDs,
            "JobID" => $jobID,
        ]);
        if (empty($activeTimedAutomationRuleIDs)) {
            $this->logger->info("No active timed automation rules found for the cron to execute.", [
                Logger::FIELD_TAGS => ["automationRules", "automationRuleJob"],
                "DateTime" => CurrentTimeStamp::getDateTime(),
                "JobID" => $jobID,
            ]);
            return JobExecutionStatus::abandoned();
        }
        try {
            foreach ($activeTimedAutomationRuleIDs as $automationRuleID) {
                $this->automationRuleModel->startAutomationRunByID(
                    $automationRuleID,
                    AutomationRuleDispatchesModel::TYPE_TRIGGERED
                );
            }
        } catch (Exception $e) {
            $error = true;
            // Log the error and continue with the next automation Rule.
            $this->logger->error("Error executing the triggered automation rule.", [
                Logger::FIELD_TAGS => ["automationRules", "automationRuleJob"],
                "DateTime" => CurrentTimeStamp::getDateTime(),
                "AutomationRuleID" => $automationRuleID,
                "Exception" => $e,
                "JobID" => $jobID,
            ]);
        }
        $this->logger->info("Finished executing timed automation hourly job.", [
            Logger::FIELD_TAGS => ["automationRules", "automationRuleJob"],
            "DateTime" => CurrentTimeStamp::getDateTime(),
            "ActiveTimedAutomationRuleIDs" => $activeTimedAutomationRuleIDs,
            "JobID" => $jobID,
        ]);

        return $error ? JobExecutionStatus::error() : JobExecutionStatus::success();
    }

    /**
     * Get the cron expression to run this job.
     *
     * @return string
     */
    public static function getCronExpression(): string
    {
        // Run every hour
        return "0 * * * *";
    }
}
