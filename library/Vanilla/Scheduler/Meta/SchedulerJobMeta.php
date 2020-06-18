<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Meta;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\TrackingSlip;

/**
 * Class SchedulerJobMeta
 */
class SchedulerJobMeta {

    /* @var string */
    protected $key = null;
    /* @var string|null */
    protected $jobId = null;
    /* @var int */
    protected $received = null;
    /* @var JobExecutionStatus */
    protected $status = null;
    /* @var string|null */
    protected $errorMessage = null;

    /**
     * SchedulerJobMeta constructor.
     *
     * @param TrackingSlip|null $trackingSlip
     */
    public function __construct(TrackingSlip $trackingSlip = null) {
        if ($trackingSlip !== null) {
            $this->key = $trackingSlip->getTrackingId();
            $this->status = $trackingSlip->getStatus();
            $this->errorMessage = $trackingSlip->getErrorMessage();
            $this->received = time();
        }
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return SchedulerJobMeta
     */
    public function setKey(string $key): SchedulerJobMeta {
        $this->key = $key;

        return $this;
    }

    /**
     * @return int
     */
    public function getReceived(): int {
        return $this->received;
    }

    /**
     * Set received timestamp
     *
     * @param int $received
     * @return SchedulerJobMeta
     */
    public function setReceived(int $received): SchedulerJobMeta {
        $this->received = $received;

        return $this;
    }

    /**
     * @return JobExecutionStatus
     */
    public function getStatus(): JobExecutionStatus {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param JobExecutionStatus $status
     * @return SchedulerJobMeta
     */
    public function setStatus(JobExecutionStatus $status): SchedulerJobMeta {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJobId(): ?string {
        return $this->jobId;
    }

    /**
     * Set jobId
     *
     * @param string|null $jobId
     * @return SchedulerJobMeta
     */
    public function setJobId(?string $jobId): SchedulerJobMeta {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string {
        return $this->errorMessage;
    }

    /**
     * Set errorMessage
     *
     * @param string|null $errorMessage
     * @return SchedulerJobMeta
     */
    public function setErrorMessage(?string $errorMessage): SchedulerJobMeta {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}
