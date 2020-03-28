<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Library\Jobs;

use Garden\Schema\Schema;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobPriority;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Delete one or more discussions.
 */
class DeleteDiscussions implements LocalJobInterface {

    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var array */
    private $discussionArray;

    /**
     * Initial job setup.
     *
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(\DiscussionModel $discussionModel) {
        $this->discussionModel = $discussionModel;
    }

    /**
     * Validate the message against the schema.
     *
     * @return Schema
     */
    private function messageSchema(): Schema {
        $schema = Schema::parse([
            "discussionID" =>
                [
                    "type" => "array",
                    "items" => [
                        "type" => "integer"
                    ]
                ]
        ]);
        return $schema;
    }

    /**
     * Delete specified discussions
     */
    public function run(): JobExecutionStatus {
        if (!is_array($this->discussionArray)) {
            return JobExecutionStatus::abandoned();
        }
        $this->discussionModel->deleteID($this->discussionArray);
        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $message = $this->messageSchema()->validate($message);
        $this->discussionArray = $message["discussionID"];
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
