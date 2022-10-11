<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\LegacyModelUtils;
use Vanilla\Models\ModelCache;
use Vanilla\Dashboard\Models\RecordStatusModel;
/**
 * Class QnaModel
 */
class QnaModel
{
    const ACCEPTED = "Accepted";
    const ANSWERED = "Answered";
    const UNANSWERED = "Unanswered";
    const TYPE = "Question";

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /**
     * Constructor for the class
     */
    public function __construct(RecordStatusModel $recordStatusModel)
    {
        $this->recordStatusModel = $recordStatusModel;
    }

    /**
     * Update Q&A counts through the dba/counts endpoint.
     *
     * @param string $column
     * @return array $results Formatted to match what "dba.js" expects
     */
    public function counts($column)
    {
        $result = ["Complete" => true];

        switch ($column) {
            // Discussion table, QnA column will be updated.
            case "QnA":
                $request = Gdn::request()->get();
                $result = $this->recalculateDiscussionQnABatches(
                    $request["NumberOfBatchesDone"] ?? 0,
                    $request["LatestID"] ?? 0
                );
                break;
        }

        return $result;
    }

    /**
     * Get the count of unanswered questions visible to the current user.
     *
     * - This count is cached based on what categories are visible to the current user.
     * - The cache here is limited by default to make it faster to calculate.
     * - The cache has a 15-minute expiry, with no manual invalidation. It's acceptable for a count to be slightly delayed.
     *
     * @param int|null $limit
     * @return int
     */
    public function getUnansweredCount(?int $limit = LegacyModelUtils::COUNT_LIMIT_DEFAULT): int
    {
        $limit = $limit ?? LegacyModelUtils::COUNT_LIMIT_DEFAULT;
        $categoryModel = CategoryModel::instance();
        $discussionModel = DiscussionModel::instance();

        $modelCache = new ModelCache("qna", Gdn::cache());

        // Will be filtered by current subcommunity automatically if they are enabled.
        $visibleCategoryIDs = $categoryModel->getVisibleCategoryIDs([
            "forceArrayReturn" => true,
            "filterHideDiscussions" => true,
            "filterArchivedCategories" => true,
        ]);

        $count = $modelCache->getCachedOrHydrate(
            ["qna/unansweredCount", "limit" => $limit, "categoryIDs" => $visibleCategoryIDs],
            function () use ($limit, $visibleCategoryIDs, $discussionModel) {
                $where = [
                    "Type" => "Question",
                    "statusID" => [QnAPlugin::DISCUSSION_STATUS_UNANSWERED, QnAPlugin::DISCUSSION_STATUS_REJECTED],
                ];
                // Visible categoryIDs can be "true" if a user has access to every category.
                if (is_array($visibleCategoryIDs)) {
                    $where["CategoryID"] = $visibleCategoryIDs;
                }
                $questionCount = LegacyModelUtils::getLimitedCount($discussionModel, $where, $limit);
                return $questionCount;
            },
            [
                Gdn_Cache::FEATURE_EXPIRY => 15 * 60, // 15 minutes.
            ]
        );
        return $count;
    }

    /**
     * Get all Question Statuses
     *
     * @param boolean $isActive
     * @return array
     */
    public function getStatuses(): array
    {
        $questionStatuses = $this->recordStatusModel->select($this->getCondition());
        return array_column($questionStatuses, null, "statusID");
    }

    /**
     * Get a Question status
     *
     * @param int $statusID
     * @return array|null
     */
    public function getStatus(int $statusID)
    {
        $where = $this->getCondition(["statusID" => $statusID]);
        try {
            return $this->recordStatusModel->selectSingle($where);
        } catch (NoResultsException $e) {
            return $this->recordStatusModel->getDefaultRecordStatusBySubType("question");
        }
    }

    /**
     * Recalculate the QnA state of discussions.
     * There is 4 possible QnA states for questions, Unanswered, Answered, Rejected and Accepted.
     * There is 3 possible QnA states for comments, Accepted, Rejected and NULL (Untreated).
     *
     * @param array $discussionIDs discussions to be recalculated
     * @throws Exception | Being thrown from the put method of the sql object
     */
    private function recalculateDiscussionsQnA($discussionIDs)
    {
        // Updating questions with accepted answers.
        Gdn::sql()
            ->update("Discussion d")
            ->join("Comment c", 'c.DiscussionID = d.DiscussionID and c.QnA = \'Accepted\'')
            ->set("d.QnA", "Accepted")
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->put();

        // Updating questions with no answers.
        Gdn::sql()
            ->update("Discussion d")
            ->leftJoin("Comment c", "c.DiscussionID = d.DiscussionID")
            ->set("d.QnA", "Unanswered")
            ->where(["c.CommentID is null" => ""])
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->put();

        // Updating questions with untreated answers but no accepted answer.
        Gdn::sql()
            ->update("Discussion d")
            ->join("Comment c", "c.DiscussionID = d.DiscussionID and c.QnA is null")
            ->leftJoin("Comment c1", 'c1.DiscussionID = d.DiscussionID and c1.QnA = \'Accepted\'')
            ->set("d.QnA", "Answered")
            ->where(["c1.CommentID is null" => ""])
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->put();

        // Updating questions with ONLY rejected answers.
        Gdn::sql()
            ->update("Discussion d")
            ->join("Comment c", 'c.DiscussionID = d.DiscussionID and c.QnA = \'Rejected\'')
            ->leftJoin("Comment c1", 'c1.DiscussionID = d.DiscussionID and (c1.QnA = \'Accepted\' OR c1.QnA is null)')
            ->set("d.QnA", "Rejected")
            ->where(["c1.CommentID is null" => ""])
            ->whereIn("d.DiscussionID", $discussionIDs)
            ->put();
    }

    /**
     * Preparing batches of discussions (questions) and triggering recalculation of their QnA state.
     * Updating records in batches of 1000 to make sure we don't lock the table for too long.
     *
     * @param integer $numberOfBatchesDone number of batches already processed
     * @param integer $latestID latest discussionID we treated
     * @return array current state of QnA recalculation. Formatted to match what "dba.js" expects
     */
    private function recalculateDiscussionQnABatches($numberOfBatchesDone, $latestID)
    {
        $perBatch = 1000;

        // Make sure we don't kill a database.
        $count = Gdn::sql()->getCount("Discussion", ["Type" => "Question"]);
        $threshold = c("Database.AlterTableThreshold", 250000);
        if ($count > $threshold) {
            throw new Exception("Amount of questions is exceeding the database threshold of " . $threshold . ".");
        }

        // Get min and max discussionID for questions
        $result = Gdn::sql()
            ->select("DiscussionID", "max", "MaxValue")
            ->select("DiscussionID", "min", "MinValue")
            ->from("Discussion")
            ->where(["Type" => "Question"])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        $totalBatches = ceil(($result["MaxValue"] - $result["MinValue"]) / $perBatch);

        $currentBatch = Gdn::sql()
            ->select("DiscussionID")
            ->from("Discussion")
            ->where([
                "DiscussionID >" => $latestID,
                "Type" => "Question",
            ])
            ->orderBy("DiscussionID")
            ->limit($perBatch)
            ->get()
            ->resultArray();

        $currentBatch = array_column($currentBatch, "DiscussionID", "DiscussionID");

        $latestID = key(array_slice($currentBatch, -1, 1, true));

        $this->recalculateDiscussionsQnA($currentBatch);

        $numberOfBatchesDone++;

        if ($totalBatches == $numberOfBatchesDone) {
            return ["Complete" => true];
        }

        return [
            "Percent" => round(($numberOfBatchesDone / $totalBatches) * 100) . "%",
            "Args" => [
                "NumberOfBatchesDone" => $numberOfBatchesDone,
                "LatestID" => $latestID,
            ],
        ];
    }

    /**
     * Get question status data by its name
     * @param string $name
     * @return array
     */
    public function getQuestionStatusByName(string $name): array
    {
        $where = $this->getCondition(["name" => $name]);
        return $this->recordStatusModel->selectSingle($where);
    }

    /**
     * Get where conditions for QuestionModel
     * @param array $additional
     * @return array
     */
    private function getCondition(array $additional = [])
    {
        $default = [
            "recordSubtype" => "question",
            "IsActive" => 1,
        ];
        return array_merge($default, $additional);
    }
}
