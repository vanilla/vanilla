<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class NonCompliantDriverSlip.
 */
class NonCompliantDriverSlip implements DriverSlipInterface {

    /**
     * Execute.
     *
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * Get the ID.
     *
     * @return string
     */
    public function getId(): string {
        return "null";
    }

    /**
     * Get the status.
     *
     * @return JobExecutionStatus
     */
    public function getStatus(): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * Get extended status.
     * @return array
     */
    public function getExtendedStatus(): array {
        return ['status' => $this->getStatus()];
    }

    /**
     * Set stack execution error message.
     *
     * @param string $msg
     * @return bool
     */
    public function setStackExecutionFailed(string $msg): bool {
        return false;
    }
}
