<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\TrackableJobAwareInterface;

/**
 * Interface TrackingSlipInterface
 */
interface TrackingSlipInterface extends TrackableJobAwareInterface
{
    /**
     * Get the jobID.
     *
     * @return string The jobID
     */
    public function getID(): string;

    /**
     * GetType
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get the job status
     */
    public function getStatus(): JobExecutionStatus;

    /**
     * @return array
     */
    public function getExtendedStatus(): array;

    /**
     * @return JobDescriptorInterface
     */
    public function getDescriptor(): JobDescriptorInterface;

    /**
     * Get the Error Message (if exists)
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * Start
     */
    public function start(): void;

    /**
     * Stop
     */
    public function stop(): void;

    /**
     * GetElapsedMs
     *
     * @return int|null
     */
    public function getElapsedMs(): ?int;

    /**
     * Log
     *
     * @return bool
     */
    public function log(): bool;

    /**
     * GetDriverSlip
     *
     * @return DriverSlipInterface
     */
    public function getDriverSlip(): DriverSlipInterface;

    /**
     * GetJobInterface
     *
     * @return string
     */
    public function getJobInterface(): string;

    /**
     * GetDuplication
     *
     * @return int
     */
    public function getDuplication(): int;

    /**
     * IncrementDuplication
     */
    public function incrementDuplication(): void;
}
