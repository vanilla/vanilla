<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Scheduler Interface
 */
interface SchedulerInterface
{
    /**
     * Add driver
     *
     * @param string $driverType
     * @return bool
     */
    public function addDriver(string $driverType): bool;

    /**
     * Get driver
     *
     * @return array
     */
    public function getDrivers(): array;

    /**
     * Add a job to be scheduled
     *
     * @param string $jobType
     * @param array $message
     * @param JobPriority|null $jobPriority
     * @param int|null $delay
     *
     * @return TrackingSlipInterface
     * @deprecated Please use `addJobDescriptor`
     *
     */
    public function addJob(
        string $jobType,
        $message = [],
        JobPriority $jobPriority = null,
        int $delay = null
    ): TrackingSlipInterface;

    /**
     * Add a job to be scheduled using a Job Descriptor
     *
     * @param JobDescriptorInterface $jobDescriptor
     * @return TrackingSlipInterface
     */
    public function addJobDescriptor(JobDescriptorInterface $jobDescriptor): TrackingSlipInterface;

    /**
     * Set the Execution Type
     *
     * @param JobExecutionType $executionType
     */
    public function setExecutionType(JobExecutionType $executionType): void;
}
