<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Driver;

use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Local Driver
 *
 * A driver that accepts jobs and process them locally on the current environment.
 */
class LocalDriver implements DriverInterface {

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LocalDriver constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Receive a JobInterface
     *
     * @param JobInterface $job
     * @return DriverSlipInterface
     * @throws \Exception The job class '%s' doesn't implement LocalJobInterface.
     */
    public function receive(JobInterface $job): DriverSlipInterface {

        if (!$job instanceof LocalJobInterface) {
            $missingInterfaceMsg = sprintf("The job class '%s' doesn't implement LocalJobInterface.", get_class($job));
            $this->logger->error($missingInterfaceMsg);
            throw new \Exception($missingInterfaceMsg);
        }

        $localDriverSlip = new LocalDriverSlip($job);

        return $localDriverSlip;
    }

    /**
     * Execute a DriverSlipInterface
     *
     * @param DriverSlipInterface $driverSlip
     * @return JobExecutionStatus
     * @throws \Exception The class `%s` doesn't implement LocalDriverSlip.
     */
    public function execute(DriverSlipInterface $driverSlip): JobExecutionStatus {

        if (!$driverSlip instanceof LocalDriverSlip) {
            $missingInterfaceMsg = sprintf("The class `%s` doesn't implement LocalDriverSlip.", get_class($driverSlip));
            $this->logger->error($missingInterfaceMsg);
            throw new \Exception($missingInterfaceMsg);
        }

        /* @var $driverSlip LocalDriverSlip */
        try {
            return $driverSlip->execute();
        } catch (\Throwable $t) {
            $msg = "Driver failed to execute Job";
            $msg .= ". Message: ".$t->getMessage();
            $msg .= ". File: ".$t->getFile();
            $msg .= ". Line: ".$t->getLine();

            $driverSlip->setStackExecutionFailed($msg);
            return $driverSlip->getStatus();
        }
    }

    /**
     * @return array
     */
    public function getSupportedInterfaces(): array {
        return [
            LocalJobInterface::class
        ];
    }
}
