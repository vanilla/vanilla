<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Library\Jobs;

use CategoryModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;

/**
 * Update category discussion and comment count.
 */
class UpdateDiscussionCount implements LocalJobInterface {

    /** @var CategoryModel */
    private $categoryModel;

    /** @var int */
    private $categoryID;

    /** @var array|null */
    private $discussion;

    /**
     * Initial job setup.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel) {
        $this->categoryModel = $categoryModel;
    }

    /**
     * Validate the message against the schema.
     *
     * @return Schema
     */
    private function messageSchema(): Schema {
        $schema = Schema::parse([
            "categoryID" => ["type" => "integer"],
            "discussion:a|n?",
        ]);

        return $schema;
    }

    /**
     * Update category discussion and comment count.
     */
    public function run(): JobExecutionStatus {
        $this->categoryModel->updateDiscussionCount($this->categoryID, $this->discussion);

        return JobExecutionStatus::complete();
    }

    /**
     * Set job Message
     *
     * @param array $message
     * @throws ValidationException On error.
     */
    public function setMessage(array $message) {
        $message = $this->messageSchema()->validate($message);
        $this->categoryID = $message["categoryID"];
        $this->discussion = $message["discussion"];
    }
}
