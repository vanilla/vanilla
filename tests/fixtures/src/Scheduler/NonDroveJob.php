<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Class NonDroveJob
 *
 * I look like a Job, but not extending any Driver interface
 */
class NonDroveJob implements JobInterface {

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $message;

    /**
     * NonDroveJob constructor.
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
     */
    public function run() {
        $this->logger->info(get_class($this)." :: ".var_export($this->message, true));
    }

    /**
     * Set the priority.
     *
     * @param JobPriority $priority
     */
    public function setPriority(JobPriority $priority) {
        // void method.
    }

    /**
     * Set the delay.
     *
     * @param integer $seconds
     */
    public function setDelay(int $seconds) {
        // void method.
    }
}
