<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Test;

use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class NonCompliantDriverSlip.
 */
class NonCompliantDriverSlip implements DriverSlipInterface {
    /**
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * @return string
     */
    public function getId(): string {
        return "null";
    }

    /**
     * @return JobExecutionStatus
     */
    public function getStatus(): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * @return array
     */
    public function getExtendedStatus(): array {
        return ['status' => $this->getStatus()];
    }

    /**
     * @param string $msg
     * @return bool
     */
    public function setStackExecutionFailed(string $msg): bool {
        return false;
    }
}
