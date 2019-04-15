<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobInterface;

/**
 * Interface DriverInterface
 */
interface DriverInterface {

    /**
     * Receive a Job
     *
     * @param JobInterface $job
     * @return DriverSlipInterface
     */
    public function receive(JobInterface $job): DriverSlipInterface;

    /**
     * Execute a Driver job
     *
     * @param DriverSlipInterface $driverSlip
     *
     * @return JobExecutionStatus
     */
    public function execute(DriverSlipInterface $driverSlip): JobExecutionStatus;

    /**
     * Get Supported interfaces.
     * Get the list of interfaces (string based) that the Driver would handle.
     *
     * @return array
     */
    public function getSupportedInterfaces(): array;
}
