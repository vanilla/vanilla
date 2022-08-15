<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

namespace Vanilla\QnA\Job;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Job to send question followup notifications.
 */
class QnaFollowupJob extends LocalApiJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * No message needed.
     *
     * @param array $message
     */
    public function setMessage(array $message)
    {
        return;
    }

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        try {
            $this->logger->info("QnA notifications start");
            $response = $this->vanillaClient->post("/discussions/question-notifications");
            $this->logger->info("QnA notifications success");
            return JobExecutionStatus::complete();
        } catch (\Exception $e) {
            $this->logger->info("QnA notifications failed", [
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "trace" => $e->getTraceAsString(),
            ]);
            return JobExecutionStatus::failed();
        }
    }
}
