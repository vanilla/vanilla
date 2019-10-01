<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobInterface;

/**
 * Class NonCompliantDriver.
 *
 * I look like a Driver, but not implementing the DriverInterface
 */
class NonCompliantDriver {

    /**
     * Receive a job.
     *
     * @param JobInterface $job
     * @return DriverSlipInterface
     */
    public function receive(JobInterface $job): DriverSlipInterface {
    }

    /**
     * Execute a driver job.
     *
     * @param DriverSlipInterface $driverSlip
     * @return JobExecutionStatus
     */
    public function execute(DriverSlipInterface $driverSlip): JobExecutionStatus {
    }
}
