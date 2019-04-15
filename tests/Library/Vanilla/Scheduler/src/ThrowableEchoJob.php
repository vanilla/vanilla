<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Test;

use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class ThrowableEchoJob
 */
class ThrowableEchoJob implements LocalJobInterface {
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var
     */
    protected $message;

    /**
     * ThrowableEchoJob constructor.
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
        nonExistentFunction();
    }
}
