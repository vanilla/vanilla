<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Execute the activity queue.
 */
class ExecuteActivityQueue implements Vanilla\Scheduler\Job\LocalJobInterface {

    /** @var ActivityModel */
    private $activityModel;

    /**
     * Initial job setup.
     *
     * @param ActivityModel $activityModel
     */
    public function __construct(ActivityModel $activityModel) {
        $this->activityModel = $activityModel;
    }

    /**
     * Execute all queued up items in the ActivityModel queue.
     */
    public function run(): JobExecutionStatus {
        $this->activityModel->saveQueue(true);

        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message) {
    }

}
