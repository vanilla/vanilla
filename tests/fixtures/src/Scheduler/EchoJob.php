<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class EchoJob.
 */
class EchoJob implements LocalJobInterface {

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $message;

    /**
     * EchoJob constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the message.
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $this->message = $message;
    }

    /**
     * Run the job.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $this->logger->info(get_class($this)." :: ".var_export($this->message, true));

        return JobExecutionStatus::complete();
    }
}
