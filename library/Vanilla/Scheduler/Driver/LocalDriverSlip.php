<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * LocalDriverSlip
 */
class LocalDriverSlip implements DriverSlipInterface {

    /** @var string */
    protected $id;

    /** @var JobExecutionStatus */
    protected $status;

    /** @var LocalJobInterface */
    protected $job;

    /** @var string */
    protected $errorMessage = null;

    /** @var string|null */
    protected $trackingId = null;

    /**
     * LocalDriverSlip constructor.
     *
     * @param LocalJobInterface $job
     */
    public function __construct(LocalJobInterface $job) {
        $this->id = uniqid('localDriverId::', true);
        $this->status = JobExecutionStatus::received();
        $this->job = $job;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * GetStatus
     *
     * @param bool $forceUpdate
     * @return JobExecutionStatus
     */
    public function getStatus(bool $forceUpdate = false): JobExecutionStatus {
        return $this->status;
    }

    /**
     * Execute
     *
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus {
        $this->status = JobExecutionStatus::progress();
        $this->status = $this->job->run();

        return $this->status;
    }

    /**
     * Get Extended Status
     *
     * @return array
     */
    public function getExtendedStatus(): array {
        $extendedStatus = [];
        $extendedStatus['status'] = $this->status;
        $extendedStatus['error'] = $this->errorMessage;

        return $extendedStatus;
    }

    /**
     * Set Stack Execution Problem
     *
     * @param string $errorMessage
     * @return $this|DriverSlipInterface
     */
    public function setStackExecutionFailed(string $errorMessage): DriverSlipInterface {
        $this->status = JobExecutionStatus::stackExecutionError();
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Set the job status
     *
     * @param JobExecutionStatus $status
     * @return $this|DriverSlipInterface
     */
    public function setStatus(JobExecutionStatus $status): DriverSlipInterface {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the Error Message (if exists)
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string {
        return $this->errorMessage;
    }

    /**
     * GetType
     *
     * @return string
     */
    public function getType(): string {
        return preg_replace('/\\\\/', '_', get_class($this->job));
    }

    /**
     * GetTrackingId
     *
     * @return string|null
     */
    public function getTrackingId(): ?string {
        return $this->trackingId;
    }

    /**
     * SetTrackingId
     *
     * @param string $trackingId
     */
    public function setTrackingId(string $trackingId): void {
        $this->trackingId = $trackingId;
    }
}
