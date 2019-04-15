<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Test;

use Vanilla\Scheduler\Driver\DriverInterface;
use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Driver\LocalDriverSlip;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Class VoidDriver
 */
class VoidDriver implements DriverInterface {

    /**
     * @param JobInterface $job
     * @return DriverSlipInterface
     */
    public function receive(JobInterface $job): DriverSlipInterface {
        return new LocalDriverSlip($job);
    }

    /**
     * @param DriverSlipInterface $driverSlip
     * @return JobExecutionStatus
     */
    public function execute(DriverSlipInterface $driverSlip): JobExecutionStatus {
        return JobExecutionStatus::complete();
    }

    /**
     * @return array
     */
    public function getSupportedInterfaces(): array {
        return [];
    }
}
