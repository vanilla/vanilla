<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

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

    /**
     * Add a new Job to the queue and immediately execute it.
     *
     * @param string $jobType
     * @param array $message
     * @param JobPriority|null $jobPriority
     * @param int|null $delay
     * @return TrackingSlipInterface
     */
    public function addJob(string $jobType, $message = [], JobPriority $jobPriority = null, int $delay = null): TrackingSlipInterface {
        $result = parent::addJob($jobType, $message, $jobPriority, $delay);

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
    }
}
