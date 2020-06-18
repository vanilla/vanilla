<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Interface DriverJobInterface
 */
interface DriverSlipInterface {
    
    /**
     * Get the job Id
     *
     * @return string The job Id
     */
    public function getId(): string;

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
     * Get the job status
     */
    public function getStatus(): JobExecutionStatus;


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
