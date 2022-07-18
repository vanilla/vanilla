<?php
/**
 * @author Sooraj FRancis <sfrancis@hhigherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license gpl-2.0-only
 */

namespace Vanilla\QnA\Models;

use Vanilla\Dashboard\Models\StatusMigrate;
use Vanilla\Web\SystemCallableInterface;

/**
 * Question Status Migrate Model
 *
 * Model for migrating qna statuses to
 * equivalent unified post status
 */
class QuestionStatusMigrate extends StatusMigrate
{
    const DISCUSSION_TYPE = "Question";

    /**
     * Update question statuses
     * @param array $discussionIDs
     * @return false|void
     */
    public function updateRecords(array $discussionIDs)
    {
        if (!count($discussionIDs)) {
            return false;
        }
        $where = $this->getDefaultCondition("d.");
        $where["d.$this->primaryKey"] = $discussionIDs;
        $where["rs.recordType"] = "discussion";
        $where["rs.recordSubtype"] = "question";
        $this->updateQuestionStatuses($where);
    }

    /**
     * Update statusID for Question type based on Qna column
     *
     * @param array $where
     * @return void
     */
    private function updateQuestionStatuses(array $where)
    {
        $key = "d." . $this->primaryKey;
        if (isset($where[$key]) && is_array($where[$key]) && count($where[$key])) {
            $sql = $this->database->createSql();
            $sql->update("{$this->table} d")
                ->join("recordStatus rs", "d.QnA = rs.name")
                ->set("d.statusID", "rs.statusID", false, false)
                ->where($where)
                ->put();
        }
    }

    /**
     * get the discussion type
     * @return string
     */
    public function getDiscussionType(): string
    {
        return self::DISCUSSION_TYPE;
    }
}
