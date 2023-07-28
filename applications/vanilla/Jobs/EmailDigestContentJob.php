<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Jobs;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\EmailDigestGenerator;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

class EmailDigestContentJob extends LocalApiJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = \Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
    }
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
     * Rotate the System token from the config file.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        $context = [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
            Logger::FIELD_TAGS => ["digest-content-generator"],
        ];
        try {
            $this->logger->info("Generating digest content.", $context);
            $digestGenerator = \Gdn::getContainer()->get(EmailDigestGenerator::class);
            $digestGenerator->generateDigestData();
            $this->logger->info("Digest content successfully generated.", $context);
            return JobExecutionStatus::complete();
        } catch (\Exception $e) {
            ErrorLogger::error(
                "Generating digest content failed",
                ["digest-content-generator"],
                $context + ["exception" => $e]
            );
            return JobExecutionStatus::failed();
        }
    }
}
