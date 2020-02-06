<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Class ParentJob.
 */
class ParentJob implements LocalJobInterface {

    /** @var LoggerInterface */
    protected $logger;

    /** @var SchedulerInterface */
    protected $scheduler;

    /** @var array */
    protected $message;

    /**
     * EchoJob constructor.
     *
     * @param LoggerInterface $logger
     * @param SchedulerInterface $scheduler
     */
    public function __construct(LoggerInterface $logger, SchedulerInterface $scheduler) {
        $this->logger = $logger;
        $this->scheduler = $scheduler;
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
        $this->logger->info(get_class($this)." :: Creating ChildJob");
        $this->scheduler->addJob(\VanillaTests\Fixtures\Scheduler\EchoJob::class);
        return JobExecutionStatus::complete();
    }

    /**
     * Set the priority.
     *
     * @param JobPriority $priority
     */
    public function setPriority(JobPriority $priority) {
        // void method. It doesn't make any sense set a priority for a LocalJob
    }

    /**
     * Set the delay.
     *
     * @param integer $seconds
     */
    public function setDelay(int $seconds) {
        // void method. It doesn't make any sense set a delay for a LocalJob
    }
}
