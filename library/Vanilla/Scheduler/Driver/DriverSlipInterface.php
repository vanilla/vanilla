<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Interface DriverJobInterface.
 */
interface DriverSlipInterface extends TrackingSlipInterface {

    /**
     * Execute
     *
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus;

    /**
     * Set Stack Execution Problem
     *
     * @param string $msg
     * @return bool
     */
    public function setStackExecutionFailed(string $msg): bool;
}
