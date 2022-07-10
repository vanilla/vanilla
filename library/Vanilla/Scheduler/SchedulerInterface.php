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
interface SchedulerInterface {

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
     * Set dispatch event name
     *
     * @param string $eventName
     * @return bool
     */
    public function setDispatchEventName(string $eventName): bool;

    /**
     * Get dispatch event name
     *
     * @return string
     */
    public function getDispatchEventName(): string;

    /**
     * Set dispatched event name
     *
     * @param string $eventName
     * @return bool
     */
    public function setDispatchedEventName(string $eventName): bool;

    /**
     * Get dispatched event name
     *
     * @return string
     */
    public function getDispatchedEventName(): string;

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

    /**
     * Whether or not to finalize the request and flush output buffers.
     *
     * In a web request, you probably want to flush buffers,
     * however in other environments, it's best not to flush buffers you didn't start.
     *
     * @return bool
     */
    public function getFinalizeRequest(): bool;

    /**
     * Set the finalize request flag.
     *
     * @param bool $finalizeRequest
     */
    public function setFinalizeRequest(bool $finalizeRequest): void;
}
