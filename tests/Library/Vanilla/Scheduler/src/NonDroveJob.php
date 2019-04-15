<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Test;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobInterface;

/**
 * Class NonDroveJob
 *
 * I look like a Job, but not extending any Driver interface
 */
class NonDroveJob implements JobInterface {
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var
     */
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
     * @param array $message
     */
    public function setMessage(array $message) {
        $this->message = $message;
    }

    /**
     *
     */
    public function run() {
        $this->logger->info(get_class($this)." :: ".var_export($this->message, true));
    }
}
