<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\Scheduler\Driver\DriverInterface;
use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Driver\LocalDriverSlip;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class ThrowableDriver.
 */
class ThrowableDriver implements DriverInterface {

    /**
     * Receive a job.
     *
     * @param JobInterface $job
     * @return DriverSlipInterface
     */
    public function receive(JobInterface $job): DriverSlipInterface {
        return new LocalDriverSlip($job);
    }

    /**
     * Execute a driver job.
     * @param DriverSlipInterface $driverSlip
     * @return JobExecutionStatus
     */
    public function execute(DriverSlipInterface $driverSlip): JobExecutionStatus {
        nonExistentFunction();
    }

    /**
     * Get supported job interfaces.
     *
     * @return array
     */
    public function getSupportedInterfaces(): array {
        return [
            LocalJobInterface::class
        ];
    }
}
