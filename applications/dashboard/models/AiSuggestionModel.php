<?php
/**
 * @author Pavel Goncharov <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Gdn_Session;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model for AiSuggestion.
 */
class AiSuggestionModel extends PipelineModel
{
    /**
     * DI.
     *
     * @param Gdn_Session $session
     */
    public function __construct(private Gdn_Session $session)
    {
        parent::__construct("aiSuggestion");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $booleanFields = new BooleanFieldProcessor(["hidden", "isDeleted"]);
        $this->addPipelineProcessor($booleanFields);
    }

    /**
     * Structure for the ai suggestion table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     *  this method will drop the table before attempting to re-create it.
     * @return void
     * @throws \Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("aiSuggestion")
            ->primaryKey("aiSuggestionID")
            ->column("discussionID", "int", false, "index")
            ->column("commentID", "int", true, "commentID")
            ->column("title", "varchar(255)")
            ->column("summary", "mediumtext")
            ->column("url", "text")
            ->column("type", "varchar(55)")
            ->column("format", "varchar(55)")
            ->column("documentID", "varchar(55)", true)
            ->column("sourceIcon", "varchar(255)", true)
            ->column("hidden", "tinyint", 0)
            ->column("isDeleted", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("insertUserID", "int")
            ->column("dateUpdated", "datetime")
            ->column("updateUserID", "int")
            ->set($explicit, $drop);
    }

    /**
     * Get A list of suggestions for a discussion
     *
     * @param int $discussionID
     * @param bool|null $isHidden
     * @return array
     */
    public function getByDiscussionID(int $discussionID, mixed $isHidden = false): array
    {
        $sql = $this->database->sql();
        $where = ["isDeleted" => false, "discussionID" => $discussionID];
        if ($isHidden !== null) {
            $where["hidden"] = $isHidden;
        }
        $result = $sql
            ->from("aiSuggestion ai")
            ->where($where)
            ->get();
        return $result->resultArray();
    }

    /**
     * Get A list of suggestions by primary IDs
     *
     * @param int $suggestionIDs
     * @return array
     */
    public function getByIDs(array $suggestionIDs): array
    {
        $sql = $this->database->sql();
        $result = $sql
            ->from("aiSuggestion ai")
            ->where(["aiSuggestionID" => $suggestionIDs])
            ->get();
        return $result->resultArray();
    }

    /**
     * Get An ai suggestion by primary ID
     *
     * @param int $suggestionID
     * @return array
     */
    public function getByID(int $suggestionID): array
    {
        $sql = $this->database->sql();
        $result = $sql
            ->from("aiSuggestion ai")
            ->where(["aiSuggestionID" => $suggestionID])
            ->get();
        return $result->firstRow(DATASET_TYPE_ARRAY) ?? [];
    }

    /**
     * Save an array of suggestions
     *
     * @param int $discussionID
     * @param array $suggestions
     * @return void
     */
    public function saveSuggestions(int $discussionID, array $suggestions)
    {
        foreach ($suggestions as $suggestion) {
            $suggestion["discussionID"] = $discussionID;
            $this->insert($suggestion);
        }
    }
}
