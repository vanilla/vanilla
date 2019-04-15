<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class TrackingSlip
 */
class TrackingSlip implements TrackingSlipInterface {

    /**
     * @var string
     */
    protected $jobInterface;

    /**
     * @var DriverSlipInterface
     */
    protected $driverSlip;

    /**
     * TrackingSlip constructor.
     *
     * @param string $jobInterface
     * @param DriverSlipInterface $driverSlip
     */
    public function __construct(string $jobInterface, DriverSlipInterface $driverSlip) {
        $this->jobInterface = $jobInterface;
        $this->driverSlip = $driverSlip;
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId(): string {
        $class = $this->jobInterface;
        $id = $this->driverSlip->getId();
        return $class."-".$id;
    }

    /**
     * Get Status
     *
     * @return JobExecutionStatus
     */
    public function getStatus(): JobExecutionStatus {
        return $this->driverSlip->getStatus();
    }

    /**
     * Get Driver Slip
     *
     * @return DriverSlipInterface
     */
    public function getDriverSlip(): DriverSlipInterface {
        return $this->driverSlip;
    }

    /**
     * Get JobInterface name
     * @return string
     */
    public function getJobInterface() {
        return $this->jobInterface;
    }

    /**
     * Get Extended Status
     *
     * @return array
     */
    public function getExtendedStatus(): array {
        return $this->driverSlip->getExtendedStatus();
    }
}
