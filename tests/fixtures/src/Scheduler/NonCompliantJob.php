<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Psr\Log\LoggerInterface;

/**
 * Class NonCompliantJob.
 *
 * I look like a Job, but not implementing the JobInterface
 */
class NonCompliantJob {

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $message;

    /**
     * NonCompliantJob constructor.
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
}
