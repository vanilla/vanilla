<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\HostedJob\Driver\HostedDriverSlip;
use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class NonCompliantDriverSlip.
 */
class NonCompliantDriverSlip implements DriverSlipInterface {

    /**
     * Execute.
     *
     * @return JobExecutionStatus
     */
    public function execute(): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * Get the ID.
     *
     * @return string
     */
    public function getId(): string {
        return HostedDriverSlip::LEGACY_NULL_STRING_VALUE;
    }

    /**
     * GetStatus
     *
     * @param bool $forceUpdate
     * @return JobExecutionStatus
     */
    public function getStatus(bool $forceUpdate = false): JobExecutionStatus {
        return JobExecutionStatus::failed();
    }

    /**
     * Get extended status.
     *
     * @return array
     */
    public function getExtendedStatus(): array {
        return ['status' => $this->getStatus()];
    }

    /**
     * Set stack execution error message.
     *
     * @param string $msg
     * @return DriverSlipInterface
     */
    public function setStackExecutionFailed(string $msg): DriverSlipInterface {
        return $this;
    }

    /**
     * Set Status
     *
     * @param JobExecutionStatus $status
     * @return DriverSlipInterface
     */
    public function setStatus(JobExecutionStatus $status): DriverSlipInterface {
        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string {
        return null;
    }

    /**
     * GetTrackingId
     *
     * @return string|null
     */
    public function getTrackingId(): ?string {
        return "unknown";
    }

    /**
     * SetTrackingId
     *
     * @param string $trackingId
     */
    public function setTrackingId(string $trackingId): void {
        // void
    }

    /**
     * GetType
     *
     * @return string
     */
    public function getType(): string {
        return "unknown";
    }
}
