<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\EventManager;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Scheduler\Driver\DriverInterface;
use Vanilla\Scheduler\Job\JobInterface;
use Garden\Container\Container;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\JobTypeAwareInterface;
use Exception;
use Throwable;

/**
 * DummyScheduler
 *
 * a.k.a first-in-first-out after-response event-fired scheduler
 *
 * Accepts jobs and process them all by delegating to an underlying Job Driver.
 */
class DummyScheduler implements SchedulerInterface {
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
     * DummyScheduler constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param EventManager $eventManager
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        EventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->container = $container;
        $this->eventManager = $eventManager;
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

            // Finish Flushes all response data to the client
            // so that job payloads can run without affecting the browser experience
            session_write_close();

            // we assume fastCgi. If that fails, go old-school
            if (!function_exists('fastcgi_finish_request') || !fastcgi_finish_request()) {
                // need to calculate content length *after* URL rewrite!
                header("Content-length: ".ob_get_length());
                ob_end_flush();
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
     * @throws Exception DummyScheduler couldn't find an appropriate driver to handle the job class `%s`.
     */
    public function addJob(string $jobType, $message = [], JobPriority $jobPriority = null, int $delay = null): TrackingSlipInterface {

        if (!$this->container->has($jobType)) {
            $missingJobMsg = "The class `$jobType` cannot be found.";
            $this->logger->error($missingJobMsg);
            throw new Exception($missingJobMsg);
        }

        // Resolve the job and set the message
        $job = $this->container->get($jobType);

        if (!$job instanceof JobInterface) {
            $missingInterfaceMsg = "The job class `$jobType` doesn't implement JobInterface.";
            $this->logger->error($missingInterfaceMsg);
            throw new Exception($missingInterfaceMsg);
        }

        $job->setPriority($jobPriority ?? JobPriority::normal());
        $job->setDelay($delay ?? 0);
        // Priority & Delay are set before the message, so the message could overwrite those values if needed
        $job->setMessage($message);

        if ($job instanceof JobTypeAwareInterface) {
            $job->setJobType($jobType);
        }

        foreach ($this->drivers as $jobInterface => $driver) {
            if ($job instanceof $jobInterface) {
                $driverSlip = $driver->receive($job);
                $trackingSlip = new TrackingSlip($jobInterface, $driverSlip);
                $this->trackingSlips[] = $trackingSlip;

                return $trackingSlip;
            }
        }

        $missingDriverMsg = "DummyScheduler couldn't find an appropriate driver to handle the job class `$jobType`.";
        $this->logger->error($missingDriverMsg);
        throw new Exception($missingDriverMsg);
    }

    /**
     * Dispatch all jobs
     *
     * @throws \Exception In case of error executing job.
     * @return void
     */
    protected function dispatchAll() {
        foreach ($this->trackingSlips as $trackingSlip) {
            try {
                $jobInterface = $trackingSlip->getJobInterface();
                $driverSlip = $trackingSlip->getDriverSlip();
                $this->drivers[$jobInterface]->execute($driverSlip);
            } catch (Throwable $t) {
                $msg = "Scheduler failed to execute Job";
                $msg .= ". Message: ".$t->getMessage();
                $msg .= ". File: ".$t->getFile();
                $msg .= ". Line: ".$t->getLine();

                $driverSlip->setStackExecutionFailed($msg);

                $this->logger->error($msg);
            }
        }

        if ($this->dispatchedEventName != null) {
            $this->eventManager->fire($this->dispatchedEventName, $this->trackingSlips);
        }
    }
}
