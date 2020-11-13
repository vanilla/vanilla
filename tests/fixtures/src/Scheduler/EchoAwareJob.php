<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobDelayAwareInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\JobPriorityAwareInterface;
use Vanilla\Scheduler\Job\JobTypeAwareInterface;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class EchoAwareJob
 */
class EchoAwareJob implements
    LocalJobInterface,
    JobTypeAwareInterface,
    JobPriorityAwareInterface,
    JobDelayAwareInterface {

    /** @var string */
    protected $jobType;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $message;

    /** @var JobPriority */
    protected $priority;

    /** @var int */
    protected $delay;

    /**
     * EchoAwareJob constructor.
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
        $msg = get_class($this)." :: JobType :: ".$this->jobType." :: Message :: ".var_export($this->message, true);
        $this->logger->info($msg);

        return JobExecutionStatus::complete();
    }

    /**
     * Set the type.
     *
     * @param string $jobType
     */
    public function setJobType(string $jobType) {
        $this->jobType = $jobType;
    }

    /**
     * Set the priority.
     *
     * @param JobPriority $priority
     */
    public function setPriority(JobPriority $priority) {
        $this->priority = $priority;
    }

    /**
     * Set the delay.
     *
     * @param int $seconds
     */
    public function setDelay(int $seconds) {
        $this->delay = $seconds;
    }
}
