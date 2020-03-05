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
        $this->dispatchAll();
        $this->trackingSlips = [];
        return $result;
    }
}
