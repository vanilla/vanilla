<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logger;
use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\Driver\DriverSlipInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\TrackableJobAwareTrait;

/**
 * Class TrackingSlip
 */
class TrackingSlip implements TrackingSlipInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TrackableJobAwareTrait;

    /**
     * @var string
     */
    protected $jobInterface;

    /**
     * @var DriverSlipInterface
     */
    protected $driverSlip;

    /**
     * @var JobDescriptorInterface
     */
    protected $jobDescriptor;

    /* @var float */
    protected $timerStart = null;

    /* @var float */
    protected $timerStop = null;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ConfigurationInterface */
    protected $config;

    /** @var int */
    protected $duplication = 0;

    /**
     * TrackingSlip constructor
     *
     * @param string $jobInterface
     * @param DriverSlipInterface $driverSlip
     * @param JobDescriptorInterface $jobDescriptor
     * @param LoggerInterface $logger
     * @param ConfigurationInterface $config
     */
    public function __construct(
        string $jobInterface,
        DriverSlipInterface $driverSlip,
        JobDescriptorInterface $jobDescriptor,
        LoggerInterface $logger,
        ConfigurationInterface $config
    ) {
        $this->jobInterface = $jobInterface;
        $this->driverSlip = $driverSlip;
        $this->jobDescriptor = $jobDescriptor;
        $this->logger = $logger;
        $this->config = $config;

        $this->trackingID = uniqid((gethostname() ?: "unknown") . "::", true);
        $this->driverSlip->setTrackingID($this->trackingID);
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getID(): string
    {
        return $this->driverSlip->getID();
    }

    /**
     * GetType
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->driverSlip->getType();
    }

    /**
     * Get Status
     *
     * @return JobExecutionStatus
     */
    public function getStatus(): JobExecutionStatus
    {
        return $this->driverSlip->getStatus();
    }

    /**
     * Get Driver Slip
     *
     * @return DriverSlipInterface
     */
    public function getDriverSlip(): DriverSlipInterface
    {
        return $this->driverSlip;
    }

    /**
     * Get JobInterface name
     *
     * @return string
     */
    public function getJobInterface(): string
    {
        return $this->jobInterface;
    }

    /**
     * Get Extended Status
     *
     * @return array
     */
    public function getExtendedStatus(): array
    {
        return $this->driverSlip->getExtendedStatus();
    }

    /**
     * @return JobDescriptorInterface
     */
    public function getDescriptor(): JobDescriptorInterface
    {
        return $this->jobDescriptor;
    }

    /**
     * Get the Error Message (if exists)
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->driverSlip->getErrorMessage();
    }

    /**
     * Set Init
     */
    public function start(): void
    {
        $this->timerStart = microtime(true);
    }

    /**
     * Set End
     */
    public function stop(): void
    {
        $this->timerStop = microtime(true);
    }

    /**
     * Get elapsed Milliseconds
     *
     * @return int|null
     */
    public function getElapsedMs(): ?int
    {
        return $this->timerStop !== null && $this->timerStart !== null
            ? (int) (($this->timerStop - $this->timerStart) * 1000)
            : 0;
    }

    /**
     * Log
     *
     * @return bool
     */
    public function log(): bool
    {
        if (!$this->config->get("Garden.Scheduler.Log", false)) {
            return false;
        }

        $values = [
            "trackingId" => $this->getTrackingID(),
            "type" => $this->getType(),
            "jobId" => $this->getID(),
            "status" => $this->getStatus()->getStatus(),
            "elapsedMs" => $this->getElapsedMs(),
        ];

        if ($this->getErrorMessage()) {
            $values["errorMessage"] = $this->getErrorMessage();
        }

        $this->logger->log(LogLevel::INFO, "", [
            "_id" => "scheduler::" . $this->getTrackingID(),
            "_version" => microtime(true) * 1000000,
            "scheduler" => $values,
            Logger::FIELD_EVENT => "scheduler",
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
        ]);

        return true;
    }

    /**
     * GetDuplication
     *
     * @return int
     */
    public function getDuplication(): int
    {
        return $this->duplication;
    }

    /**
     * IncrementDuplication
     */
    public function incrementDuplication(): void
    {
        $this->duplication++;
    }
}
