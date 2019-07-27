<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Test;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\JobTypeAwareInterface;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class EchoJob.
 */
class EchoAwareJob implements LocalJobInterface, JobTypeAwareInterface {
    protected $jobType;
    protected $logger;
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
     * @param array $message
     */
    public function setMessage(array $message) {
        $this->message = $message;
    }

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $this->logger->info(get_class($this)." :: JobType :: ".$this->jobType." :: Message :: ".var_export($this->message, true));

        return JobExecutionStatus::complete();
    }

    public function setJobType(string $jobType) {
        $this->jobType = $jobType;
    }

    public function setPriority(JobPriority $priority) {
        // void method. It doesn't make any sense set a priority for a LocalJob
    }

    public function setDelay(int $seconds) {
        // void method. It doesn't make any sense set a delay for a LocalJob
    }
}
