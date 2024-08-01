<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\TrackableJobAwareInterface;
use Vanilla\Scheduler\Job\TrackableJobAwareTrait;

/**
 * Interface representing a job that has been received by a particular driver.
 */
interface DriverSlipInterface extends TrackableJobAwareInterface
{
    /**
     * Get the jobID
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
     * Execute
     *
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus;

    /**
     * Set the job status
     *
     * @param JobExecutionStatus $status
     * @return DriverSlipInterface
     */
    public function setStatus(JobExecutionStatus $status): DriverSlipInterface;

    /**
     * GetStatus
     *
     * @param bool $forceUpdate Forcely update the status when possible
     * @return JobExecutionStatus
     */
    public function getStatus(bool $forceUpdate = false): JobExecutionStatus;

    /**
     * @return array
     */
    public function getExtendedStatus(): array;

    /**
     * Set Stack Execution Problem
     *
     * @param string $errorMessage
     * @return DriverSlipInterface
     */
    public function setStackExecutionFailed(string $errorMessage): DriverSlipInterface;

    /**
     * Get the Error Message (if exists)
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string;
}
