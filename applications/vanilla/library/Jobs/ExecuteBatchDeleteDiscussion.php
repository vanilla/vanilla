<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;

/**
 * Execute the discussion delete queue.
 */
class ExecuteBatchDeleteDiscussion implements Vanilla\Scheduler\Job\LocalJobInterface {

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var array */
    private $message;

    /**
     * Initial job setup.
     *
     * @param DiscussionModel $discussionModel
     */
    public function __construct(DiscussionModel $discussionModel) {
        $this->discussionModel = $discussionModel;
    }

    /**
     * Validate the message against the schema.
     * 
     * @return Schema
     */
    private function messageSchema(): Schema {
        $schema = Schema::parse([
            "discussionID" => [
                [
                    "type" => "integer"
                ]
            ]
        ]);
        return $schema;
    }

    /**
     * Execute all queued up items in the ActivityModel queue.
     */
    public function run(): JobExecutionStatus {
        if (!is_array($this->message)) {
            return JobExecutionStatus::abandoned();
        }
        $this->discussionModel->deleteID($this->message);
        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $message = $this->messageSchema()->validate($message);
        $this->message = $message;
    }

    /**
     * Set job priority
     *
     * @param JobPriority $priority
     * @return void
     */
    public function setPriority(JobPriority $priority) {
    }

    /**
     * Set job execution delay
     *
     * @param int $seconds
     * @return void
     */
    public function setDelay(int $seconds) {
    }
}
