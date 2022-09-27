<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Exception;
use Garden\Container\Container;
use Garden\EventManager;
use Gdn_Cache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Throwable;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\Descriptor\CronJobDescriptorInterface;
use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Driver\DriverInterface;
use Vanilla\Scheduler\Job\JobDelayAwareInterface;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\Job\JobInterface;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\JobPriorityAwareInterface;
use Vanilla\Scheduler\Job\JobStatusModel;
use Vanilla\Scheduler\Job\JobTypeAwareInterface;
use Vanilla\Scheduler\Job\TrackableJobAwareInterface;

/**
 * DeferredScheduler.
 *
 * a.k.a first-in-first-out after-response event-fired scheduler
 *
 * Accepts jobs and process them all by delegating to an underlying Job Driver.
 */
class DeferredScheduler implements SchedulerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var JobExecutionType */
    protected $executionType;

    /** @var TrackingSlip[] */
    protected $trackingSlips = [];

    /** @var Container */
    protected $container;

    /** @var DriverInterface[] */
    protected $drivers = [];

    /** @var EventManager */
    protected $eventManager = null;

    /** @var JobStatusModel */
    protected $jobStatusModel;

    /** @var bool */
    protected $logErrorsAsWarnings = false;

    /** @var CronModel */
    protected $cronModel;

    /** @var Gdn_Cache */
    protected $cache;

    /** @var ConfigurationInterface */
    protected $config;

    /**
     * DeferredScheduler constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param EventManager $eventManager
     * @param CronModel $cronModel
     * @param Gdn_Cache $cache
     * @param ConfigurationInterface $config
     * @param JobStatusModel $jobStatusModel
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        EventManager $eventManager,
        CronModel $cronModel,
        Gdn_Cache $cache,
        ConfigurationInterface $config,
        JobStatusModel $jobStatusModel
    ) {
        $this->logger = $logger;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->cronModel = $cronModel;
        $this->cache = $cache;
        $this->config = $config;
        $this->executionType = JobExecutionType::normal();
        $this->jobStatusModel = $jobStatusModel;
    }

    /**
     * Add a Driver to the Scheduler
     *
     * @param string $driverType
     *
     * @return bool
     * @throws Exception The class `%s` cannot be found.
     * @throws Exception The class `%s` doesn't implement DriverInterface.
     */
    public function addDriver(string $driverType): bool
    {
        if (!$this->container->has($driverType)) {
            $missingDriverMsg = "The class `$driverType` cannot be found.";
            $this->logger->error($missingDriverMsg);
            throw new Exception($missingDriverMsg);
        }

        $driver = $this->container->get($driverType);

        if (!$driver instanceof DriverInterface) {
            $missingInterfaceMsg = "The class `$driverType` doesn't implement DriverInterface.";
            $this->logger->error($missingInterfaceMsg);
            throw new Exception($missingInterfaceMsg);
        }

        $jobTypes = $driver->getSupportedInterfaces();

        if (count($jobTypes) === 0) {
            $driverDoesntSupportAnyTypeMsg = "The class `$driverType` doesn't support any Job implementation.";
            $this->logger->error($driverDoesntSupportAnyTypeMsg);
            throw new Exception($driverDoesntSupportAnyTypeMsg);
        }

        foreach ($jobTypes as $jobType) {
            $this->drivers[$jobType] = $driver;
        }

        return true;
    }

    /**
     * Get drivers
     *
     * @return array
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Add new Job
     *
     * @param string $jobType
     * @param array $message
     * @param JobPriority|null $jobPriority
     * @param int|null $delay
     * @return TrackingSlipInterface
     *
     * @throws Exception The class `%s` cannot be found.
     * @throws Exception The job class `%s` doesn't implement JobInterface.
     * @throws Exception Missing driver to handle the job class `%s`.
     */
    public function addJob(
        string $jobType,
        $message = [],
        JobPriority $jobPriority = null,
        int $delay = null
    ): TrackingSlipInterface {
        $jobDescriptor = new NormalJobDescriptor($jobType);
        $jobDescriptor->setMessage($message);
        $jobDescriptor->setPriority($jobPriority ?? JobPriority::normal());
        $jobDescriptor->setDelay($delay ?? 0);

        return $this->addJobDescriptor($jobDescriptor);
    }

    /**
     * Add JobDescriptor
     *
     * @param JobDescriptorInterface $jobDescriptor
     * @return TrackingSlipInterface
     * @throws Exception The class `%s` cannot be found.
     * @throws Exception The job class `%s` doesn't implement JobInterface.
     * @throws Exception Missing driver to handle the job class `%s`.
     */
    public function addJobDescriptor(JobDescriptorInterface $jobDescriptor): TrackingSlipInterface
    {
        $hash = $jobDescriptor->getHash();
        for ($index = 0; $index < count($this->trackingSlips); $index++) {
            if ($this->trackingSlips[$index]->getDescriptor()->getHash() === $hash) {
                $this->trackingSlips[$index]->incrementDuplication();

                return $this->trackingSlips[$index];
            }
        }

        if (!$this->container->has($jobDescriptor->getJobType())) {
            $missingJobMsg = "The class `{$jobDescriptor->getJobType()}` cannot be found.";
            $this->logger->error($missingJobMsg);
            throw new Exception($missingJobMsg);
        }

        // Resolve the job and set the message
        $job = $this->container->get($jobDescriptor->getJobType());

        if (!$job instanceof JobInterface) {
            $missingInterfaceMsg = "The job class `{$jobDescriptor->getJobType()}` doesn't implement JobInterface.";
            $this->logger->error($missingInterfaceMsg);
            throw new Exception($missingInterfaceMsg);
        }

        if ($job instanceof JobTypeAwareInterface) {
            $job->setJobType($jobDescriptor->getJobType());
        }

        if ($job instanceof JobPriorityAwareInterface) {
            $job->setPriority($jobDescriptor->getPriority());
        }

        if ($job instanceof JobDelayAwareInterface) {
            $job->setDelay($jobDescriptor->getDelay());
        }

        if ($jobDescriptor instanceof TrackableJobAwareInterface && $job instanceof TrackableJobAwareInterface) {
            $job->setTrackingUserID($jobDescriptor->getTrackingUserID());
        }

        // Type, Priority & Delay are set before the message, so the message could overwrite those values if needed
        $job->setMessage($jobDescriptor->getMessage());

        foreach ($this->drivers as $jobInterface => $driver) {
            if ($job instanceof $jobInterface) {
                $driverSlip = $driver->receive($job);

                $trackingSlip = new TrackingSlip(
                    $jobInterface,
                    $driverSlip,
                    $jobDescriptor,
                    $this->logger,
                    $this->config
                );

                if ($job instanceof TrackableJobAwareInterface) {
                    $job->setTrackingID($trackingSlip->getTrackingID());

                    if ($trackingUserID = $job->getTrackingUserID()) {
                        $this->jobStatusModel->insertDriverSlip($driverSlip, $trackingUserID);
                    }
                }

                $trackingSlip->log();
                $this->trackingSlips[] = $trackingSlip;

                return $trackingSlip;
            }
        }

        $missingDriverMsg = "Missing driver to handle the job class `{$jobDescriptor->getJobType()}`.";
        $this->logger->error($missingDriverMsg);
        throw new Exception($missingDriverMsg);
    }

    /**
     * Finalize the request and flush output buffers.
     *
     * In a web request, you probably want to flush buffers,
     * however in other environments, it's best not to flush buffers you didn't start.
     *
     * @return void
     */
    public function finalizeRequest(): void
    {
        // Finish Flushes all response data to the client
        // so that job payloads can run without affecting the browser experience
        session_write_close();

        // We assume fastCgi. If that fails, go old-school.
        if (!function_exists("fastcgi_finish_request") || !fastcgi_finish_request()) {
            // need to calculate content length *after* URL rewrite!
            if (headers_sent() === false) {
                header("Content-length: " . ob_get_length());
            }
            ob_end_flush();
        }
    }

    /**
     * @param JobExecutionType $executionType
     */
    public function setExecutionType(JobExecutionType $executionType): void
    {
        $this->executionType = $executionType;
    }

    /**
     * Dispatch all of our current tracking slips.
     *
     * @return TrackingSlip[]
     */
    public function dispatchJobs(): array
    {
        // Not a cron, just dispatch the jobs.
        if (!$this->executionType->is(JobExecutionType::cron())) {
            return $this->dispatchAll();
        }

        /**
         * Silently avoid race condition for CronJobs
         * --
         * If the execution type is CRON, we want to avoid a race conditions and as a consequence
         * triggering more than once the execution of a Job on the same time frame.
         */
        $cronLock = $this->cronModel->createLock();
        try {
            if (!$cronLock->acquire()) {
                // We can't acquire a lock which means another process is running a cron.
                for ($index = 0; $index < count($this->trackingSlips); $index++) {
                    $this->trackingSlips[$index]->getDriverSlip()->setStatus(JobExecutionStatus::abandoned());
                    $this->trackingSlips[$index]->log();
                }

                return $this->trackingSlips;
            }

            return $this->dispatchAll();
        } finally {
            $this->cronModel->trackRun();
            $cronLock->release();
        }
    }

    /**
     * Dispatch all jobs
     *
     * @return TrackingSlip[]
     * @throws Exception In case of error executing job.
     */
    protected function dispatchAll(): array
    {
        /** @var TrackingSlip $trackingSlip */
        foreach ($this->generateTrackingSlips() as $trackingSlip) {
            $trackingSlip->start();
            try {
                $driverSlip = $trackingSlip->getDriverSlip();
                $jobDescriptor = $trackingSlip->getDescriptor();

                $isAllowedExecutionType = $jobDescriptor->canExecuteForType($this->executionType);
                $isUnscheduledCron =
                    $jobDescriptor instanceof CronJobDescriptorInterface &&
                    !$this->cronModel->shouldRun($jobDescriptor);

                if (!$isAllowedExecutionType || $isUnscheduledCron) {
                    // Abandon the job. It's not supposed to run right now.
                    $driverSlip->setStatus(JobExecutionStatus::abandoned());
                } else {
                    // Run the job.
                    $jobInterface = $trackingSlip->getJobInterface();
                    $this->drivers[$jobInterface]->execute($driverSlip);
                }
            } catch (Throwable $t) {
                $msg = $t->getMessage();
                if (strpos($msg, "File: ") !== false) {
                    $msg = "Scheduler failed to execute Job";
                    $msg .= ". Message: " . $t->getMessage();
                    $msg .= ". File: " . $t->getFile();
                    $msg .= ". Line: " . $t->getLine();
                }

                if (isset($driverSlip)) {
                    $driverSlip->setStackExecutionFailed($msg);
                }

                if ($this->logErrorsAsWarnings) {
                    trigger_error($msg, E_USER_ERROR);
                }

                $this->logger->error($msg);
            } finally {
                $trackingSlip->stop();
                $trackingSlip->log();
                if (isset($driverSlip)) {
                    $this->jobStatusModel->updateDriverSlip($driverSlip);
                }
            }
        }
        return $this->trackingSlips;
    }

    /**
     * Tracking slip generator.
     */
    protected function generateTrackingSlips()
    {
        // Ensure we're starting at the start.
        reset($this->trackingSlips);
        while (($key = key($this->trackingSlips)) !== null) {
            // Get the value. Advance the internal pointer.
            $slip = $this->trackingSlips[$key];
            next($this->trackingSlips);

            yield $slip;
        }
        // Reset the internal pointer to hopefully avoid unexpected behavior.
        reset($this->trackingSlips);
    }

    /**
     * Reset the scheduler.
     */
    public function reset()
    {
        $this->trackingSlips = [];
    }
}
