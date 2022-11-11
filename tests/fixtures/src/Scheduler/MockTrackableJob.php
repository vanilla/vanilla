<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Scheduler;

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Scheduler\Job\TrackableJobAwareInterface;
use Vanilla\Scheduler\Job\TrackableJobAwareTrait;

/**
 * Trackable job for tests that returns the status it was created with.
 */
class MockTrackableJob implements LocalJobInterface, TrackableJobAwareInterface
{
    use TrackableJobAwareTrait;

    /** @var string */
    private $status;

    /**
     * @param array $message
     */
    public function setMessage(array $message)
    {
        $this->status = $message["status"];
    }

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        return JobExecutionStatus::looseStatus($this->status);
    }
}
