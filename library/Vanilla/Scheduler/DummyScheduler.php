<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Cron\CronExpression;
use DateTime;
use Exception;
use Garden\Container\Container;
use Garden\EventManager;
use Gdn_Cache;
use Psr\Container\ContainerInterface;
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
use Vanilla\Scheduler\Job\JobTrackingIdAwareInterface;
use Vanilla\Scheduler\Job\JobTypeAwareInterface;
use Vanilla\Scheduler\Meta\SchedulerControlMeta;
use Vanilla\Scheduler\Meta\SchedulerMetaDao;

/**
 * DummyScheduler
 *
 * a.k.a first-in-first-out after-response event-fired scheduler
 *
 * Accepts jobs and process them all by delegating to an underlying Job Driver.
 */
class DummyScheduler implements SchedulerInterface {
    protected const CRON_LOCK_KEY = 'CRON_LOCK';
    protected const CRON_MINIMUM_TIME_SPAN = 60;

    /**
     * @var JobExecutionType
     */
    protected $executionType;

    /**
     * @var TrackingSlip[]
     */
    protected $trackingSlips = [];

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DriverInterface[]
     */
    protected $drivers = [];

    /**
     * @var EventManager
     */
    protected $eventManager = null;

    /**
     * @var string
     */
    protected $dispatchEventName = null;

    /**
     * @var string
     */
    protected $dispatchedEventName = null;

    /**
     * @var bool
     */
    protected $finalizeRequest = true;

    /** @var bool */
    protected $logErrorsAsWarnings = false;

    /**
     * @var SchedulerMetaDao
     */
    protected $schedulerMetaDao;

    /**
     * @var Gdn_Cache
     */
    protected $cache;
    /**
     * @var ConfigurationInterface
     */
    protected $config;

    /**
     * DummyScheduler constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param EventManager $eventManager
     * @param SchedulerMetaDao $schedulerMetaDao
     * @param Gdn_Cache $cache
     * @param ConfigurationInterface $config
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        EventManager $eventManager,
        SchedulerMetaDao $schedulerMetaDao,
        Gdn_Cache $cache,
        ConfigurationInterface $config
    ) {
        $this->logger = $logger;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->schedulerMetaDao = $schedulerMetaDao;
        $this->cache = $cache;
        $this->config = $config;
        $this->executionType = JobExecutionType::normal();
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
    public function addDriver(string $driverType): bool {

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
    public function getDrivers(): array {
        return $this->drivers;
    }

    /**
     * Set dispatch event name
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function setDispatchEventName(string $eventName): bool {
        $this->dispatchEventName = $eventName;

        // Hook to process all jobs when the $eventName is fired
        $this->eventManager->bind($this->dispatchEventName, function () {

            if (count($this->trackingSlips) == 0) {
                // If there is nothing to do -> return false
                if ($this->dispatchedEventName != null) {
                    $this->eventManager->fire($this->dispatchedEventName, $this->trackingSlips);
                }

                return false;
            }

            /**
             * Silently avoid race condition for CronJobs
             * --
             * If the execution type is CRON, we want to avoid a race conditions and as a consequence
             * triggering more than once the execution of a Job on the same time frame.
             * The time frame is defined by 'Garden.Scheduler.CronMinimumTimeSpan'
             * Default: self::CRON_MINIMUM_TIME_SPAN
             */
            if ($this->executionType->is(JobExecutionType::cron())) {
                $shouldAbort = false;
                $minTime = $this->config->get('Garden.Scheduler.CronMinimumTimeSpan', self::CRON_MINIMUM_TIME_SPAN);

                if ($this->cache->activeEnabled()) {
                    // If we have cache, we rely on its atomic mechanism
                    if ($this->cache->add(self::CRON_LOCK_KEY, uniqid(), [Gdn_Cache::FEATURE_EXPIRY => $minTime])
                        !== Gdn_Cache::CACHEOP_SUCCESS
                    ) {
                        $shouldAbort = true;
                    }
                } else {
                    $schedulerControlMeta = $this->schedulerMetaDao->getControl();
                    if ($schedulerControlMeta !== null) {
                        if (time() < $schedulerControlMeta->getLockTime() + $minTime) {
                            $shouldAbort = true;
                        }
                    }
                }

                if ($shouldAbort) {
                    /** @var TrackingSlip $trackingSlip */
                    foreach ($this->generateTrackingSlips() as $trackingSlip) {
                        $schedulerMeta = $trackingSlip->getSchedulerJobMeta();
                        $schedulerMeta->setStatus(JobExecutionStatus::abandoned());
                        $this->schedulerMetaDao->putJob($schedulerMeta);
                    }

                    return false;
                }

                $this->schedulerMetaDao->putControl(new SchedulerControlMeta());
            }

            /**
             * Release the Request before dispatching Jobs
             */
            if ($this->getFinalizeRequest()) {
                // Finish Flushes all response data to the client
                // so that job payloads can run without affecting the browser experience
                session_write_close();

                // We assume fastCgi. If that fails, go old-school.
                if (!function_exists('fastcgi_finish_request') || !fastcgi_finish_request()) {
                    // need to calculate content length *after* URL rewrite!
                    if (headers_sent() === false) {
                        header("Content-length: ".ob_get_length());
                    }
                    ob_end_flush();
                }
            }

            $this->dispatchAll();

            return true;
        });

        return true;
    }

    /**
     * Get dispatch event name
     *
     * @return string
     */
    public function getDispatchEventName(): string {
        return $this->dispatchEventName;
    }

    /**
     * Set dispatched event name
     *
     * @param string $eventName
     * @return bool
     */
    public function setDispatchedEventName(string $eventName): bool {
        $this->dispatchedEventName = $eventName;

        return true;
    }

    /**
     * Get dispatched event name
     *
     * @return string
     */
    public function getDispatchedEventName(): string {
        return $this->dispatchedEventName;
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
    public function addJobDescriptor(JobDescriptorInterface $jobDescriptor): TrackingSlipInterface {

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

        // Type, Priority & Delay are set before the message, so the message could overwrite those values if needed
        $job->setMessage($jobDescriptor->getMessage());

        foreach ($this->drivers as $jobInterface => $driver) {
            if ($job instanceof $jobInterface) {
                $driverSlip = $driver->receive($job);

                $trackingSlip = new TrackingSlip($jobInterface, $driverSlip, $jobDescriptor);

                if ($job instanceof JobTrackingIdAwareInterface) {
                    $job->setTrackingId($trackingSlip->getTrackingId());
                }

                $this->schedulerMetaDao->putJob($trackingSlip->getSchedulerJobMeta());
                $this->trackingSlips[] = $trackingSlip;

                return $trackingSlip;
            }
        }

        $missingDriverMsg = "Missing driver to handle the job class `{$jobDescriptor->getJobType()}`.";
        $this->logger->error($missingDriverMsg);
        throw new Exception($missingDriverMsg);
    }

    /**
     * Dispatch all jobs
     *
     * @return void
     * @throws Exception In case of error executing job.
     */
    protected function dispatchAll() {
        /** @var TrackingSlip $trackingSlip */
        foreach ($this->generateTrackingSlips() as $trackingSlip) {
            $schedulerMeta = $trackingSlip->getSchedulerJobMeta();
            try {
                $driverSlip = $trackingSlip->getDriverSlip();
                $jobDescriptor = $trackingSlip->getDescriptor();

                if (!$this->executionType->is($jobDescriptor->getExecutionType()) ||
                    ($jobDescriptor instanceof CronJobDescriptorInterface && !$this->shouldRun($jobDescriptor))
                ) {
                    $driverSlip->setStatus(JobExecutionStatus::abandoned());
                } else {
                    $jobInterface = $trackingSlip->getJobInterface();
                    $this->drivers[$jobInterface]->execute($driverSlip);
                }
                $schedulerMeta->setJobId($trackingSlip->getId());
            } catch (Throwable $t) {
                $msg = $t->getMessage();
                if (strpos($msg, "File: ") !== false) {
                    $msg = "Scheduler failed to execute Job";
                    $msg .= ". Message: ".$t->getMessage();
                    $msg .= ". File: ".$t->getFile();
                    $msg .= ". Line: ".$t->getLine();
                }

                if (isset($driverSlip)) {
                    $driverSlip->setStackExecutionFailed($msg);
                }

                if ($this->logErrorsAsWarnings) {
                    trigger_error($msg, E_USER_ERROR);
                }
                $this->logger->error($msg);
            } finally {
                $schedulerMeta->setStatus($trackingSlip->getStatus());
                $schedulerMeta->setErrorMessage($trackingSlip->getErrorMessage());
                $this->schedulerMetaDao->putJob($schedulerMeta);
            }
        }

        if ($this->dispatchedEventName != null) {
            $this->eventManager->fire($this->dispatchedEventName, $this->trackingSlips);
        }
    }

    /**
     * Tracking slip generator.
     */
    private function generateTrackingSlips() {
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
     * Whether or not to finalize the request and flush output buffers.
     *
     * In a web request, you probably want to flush buffers,
     * however in other environments, it's best not to flush buffers you didn't start.
     *
     * @return bool
     */
    public function getFinalizeRequest(): bool {
        return $this->finalizeRequest;
    }

    /**
     * Set the finalize request flag.
     *
     * @param bool $finalizeRequest
     */
    public function setFinalizeRequest(bool $finalizeRequest): void {
        $this->finalizeRequest = $finalizeRequest;
    }

    /**
     * @param JobExecutionType $executionType
     */
    public function setExecutionType(JobExecutionType $executionType): void {
        $this->executionType = $executionType;
    }

    /**
     * ShouldRun
     *
     * @param CronJobDescriptorInterface $jobDescriptor
     * @return bool
     * @throws Exception On DateTime conversion error.
     */
    protected function shouldRun(CronJobDescriptorInterface $jobDescriptor): bool {
        // A "commented" cron schedule will be skipped
        if (strpos($jobDescriptor->getSchedule(), '#') === 0) {
            return false;
        }

        $cron = CronExpression::factory($jobDescriptor->getSchedule());

        $schedulerControlMeta = $this->schedulerMetaDao->getControl();
        if ($schedulerControlMeta !== null) {
            $lastTimestamp = $this->schedulerMetaDao->getControl()->getLockTime();
        } else {
            // We go 1 day in the past.
            $lastTimestamp = time() - 24 * 60 * 60;
        }

        $nextRun = $cron->getNextRunDate(new DateTime("@$lastTimestamp"), 0, true);

        return (new DateTime('now') >= $nextRun);
    }
}
