<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\DeferredScheduler;
use Vanilla\Scheduler\TrackingSlip;
use Vanilla\Scheduler\TrackingSlipInterface;

/**
 * Class InstantScheduler
 */
class InstantScheduler extends DeferredScheduler
{
    /**
     * @var int Used to keep jobs executing in the order they are queued.
     *
     * If an instant job queues another instant job, the second one should not execute until after the first one has completed.
     */
    private $isDispatching = false;

    /**
     * @var bool Used to pause execution of jobs.
     */
    private $isPaused = false;

    protected $logErrorsAsWarnings = true;

    private $scheduledJobs = [];

    /**
     * Assert a job type was scheduled with a specific message.
     *
     * @param string $expectedType
     * @param array|null $expectedMessage If null don't check the message.
     * @param string $message
     */
    public function assertJobScheduled(string $expectedType, ?array $expectedMessage = null, string $message = ""): void
    {
        if ($expectedMessage !== null) {
            ksort($expectedMessage);
        }
        $message = $message ?: "Job was not scheduled: {$expectedType}";

        $result = false;
        if (array_key_exists($expectedType, $this->scheduledJobs)) {
            TestCase::assertTrue(true);
            if ($expectedMessage === null) {
                return;
            }

            foreach ($this->scheduledJobs[$expectedType] as $jobMessage) {
                ksort($jobMessage);
                try {
                    TestCase::assertSame($expectedMessage, $jobMessage);
                    $result = true;
                    break;
                } catch (ExpectationFailedException $e) {
                    continue;
                }
            }
        }

        TestCase::assertTrue($result, $message);
    }

    /**
     * Add a new job descriptor and immediate dispatch the queue.
     *
     * @param JobDescriptorInterface $jobDescriptor
     * @return TrackingSlipInterface
     */
    public function addJobDescriptor(JobDescriptorInterface $jobDescriptor): TrackingSlipInterface
    {
        $result = parent::addJobDescriptor($jobDescriptor);

        $type = $jobDescriptor->getJobType();
        $this->scheduledJobs[$type][] = $jobDescriptor->getMessage();

        if (!$this->isDispatching) {
            // We are already executing a job. The newly queued job is pushed onto the end of the driver slips.
            // This way the jobs fully execute in the order they are queued.
            $this->dispatchAll();
        }

        return $result;
    }

    /**
     * Override to track execution.
     *
     * @inheritdoc
     */
    protected function dispatchAll(): array
    {
        if ($this->isPaused) {
            return [];
        }
        try {
            $this->isDispatching = true;
            return parent::dispatchAll();
        } finally {
            $this->isDispatching = false;
            $this->trackingSlips = [];
        }
    }

    /**
     * Pause execution of jobs.
     */
    public function pause()
    {
        $this->isPaused = true;
    }

    /**
     * Resume execution of jobs. Any scheduled jobs will be executed immediately after resume.
     */
    public function resume()
    {
        $this->isPaused = false;
        return $this->dispatchAll();
    }

    /**
     * @return TrackingSlip[]
     */
    public function getTrackingSlips(): array
    {
        return $this->trackingSlips;
    }

    /**
     * Reset the scheduler.
     */
    public function reset()
    {
        $this->trackingSlips = [];
        $this->isPaused = false;
        $this->isDispatching = false;
    }
}
