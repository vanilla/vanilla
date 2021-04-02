<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\Descriptor\JobDescriptorInterface;
use Vanilla\Scheduler\DummyScheduler;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\TrackingSlipInterface;

/**
 * Class InstantScheduler
 */
class InstantScheduler extends DummyScheduler {

    /**
     * @var int Used to keep jobs executing in the order they are queued.
     *
     * If an instant job queues another instant job, the second one should not execute until after the first one has completed.
     */
    private $isDispatching = false;

    protected $logErrorsAsWarnings = true;

    private $scheduledJobs = [];

    /**
     * Add a new Job to the queue and immediately execute it.
     *
     * @param string $jobType
     * @param array $message
     * @param JobPriority|null $jobPriority
     * @param int|null $delay
     * @return TrackingSlipInterface
     * @throws Exception On error.
     */
    public function addJob(
        string $jobType,
        $message = [],
        JobPriority $jobPriority = null,
        int $delay = null
    ): TrackingSlipInterface {
        $result = parent::addJob($jobType, $message, $jobPriority, $delay);
        return $result;
    }

    /**
     * Assert a job type was scheduled with a specific message.
     *
     * @param string $expectedType
     * @param array $expectedMessage
     * @param string $message
     */
    public function assertJobScheduled(string $expectedType, array $expectedMessage, string $message = ""): void {
        ksort($expectedMessage);
        $message = $message ?: "Job was not scheduled: {$expectedType}";

        $result = false;
        if (array_key_exists($expectedType, $this->scheduledJobs)) {
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
    public function addJobDescriptor(JobDescriptorInterface $jobDescriptor): TrackingSlipInterface {
        $result = parent::addJobDescriptor($jobDescriptor);

        $type = $jobDescriptor->getJobType();
        if (array_key_exists($type, $this->scheduledJobs)) {
            $this->scheduledJobs[$type][] = $jobDescriptor->getMessage();
        } else {
            $this->scheduledJobs[$type] = [$jobDescriptor->getMessage()];
        }

        if (!$this->isDispatching) {
            // We are already executing a job. The newly queued job is pushed onto the end of the driver slips.
            // This way the jobs fully execute in the order they are queued.
            $this->dispatchAll();
            $this->trackingSlips = [];
        }

        return $result;
    }

    /**
     * Override to track execution.
     * @inheritdoc
     */
    protected function dispatchAll() {
        $this->isDispatching = true;
        parent::dispatchAll();
        $this->isDispatching = false;
    }
}
