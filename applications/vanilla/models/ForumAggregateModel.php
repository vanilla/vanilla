<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Garden\EventManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Database\SetLiterals\SetLiteral;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\CallbackJob;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Model for manipulating aggregate counts and posts.
 */
class ForumAggregateModel
{
    private \Gdn_Database $db;
    private \DiscussionModel $discussionModel;
    private \CategoryModel $categoryModel;
    private ConfigurationInterface $configuration;
    private SchedulerInterface $scheduler;
    private EventManager $eventManager;
    private array $deferredRecentPostUpdates = [];

    /**
     * @param \Gdn_Database $db
     * @param \DiscussionModel $discussionModel
     * @param \CategoryModel $categoryModel
     * @param ConfigurationInterface $configuration
     * @param SchedulerInterface $scheduler
     * @param EventManager $eventManager
     */
    public function __construct(
        \Gdn_Database $db,
        \DiscussionModel $discussionModel,
        \CategoryModel $categoryModel,
        ConfigurationInterface $configuration,
        SchedulerInterface $scheduler,
        EventManager $eventManager
    ) {
        $this->db = $db;
        $this->discussionModel = $discussionModel;
        $this->categoryModel = $categoryModel;
        $this->configuration = $configuration;
        $this->scheduler = $scheduler;
        $this->eventManager = $eventManager;
    }

    /**
     * For a given discussion get overrides for values that should be set.
     *
     * - If a discussion is sunk, we don't update it's `DateLastComment` or `hot` field.
     * - There is an event hook where troll management may hook in a override the sink value for a discussion.
     *
     * @param array $discussion
     * @param ?array $newComment
     *
     * @return array
     */
    private function getSinkOverrides(array $discussion, ?array $newComment): array
    {
        $buryValue = $this->configuration->get("Vanilla.Reactions.BuryValue", -5);
        $sinkValue = (bool) ($discussion["Sink"] ?? false);
        $scoreValue = $discussion["Score"] ?? 0;
        $isSunk = $sinkValue || $scoreValue <= $buryValue;

        $isSunk = $this->eventManager->fireFilter("forumAggregateModel_sinkOverrides", $isSunk, [
            "discussion" => $discussion,
            "newComment" => $newComment,
        ]);

        if ($isSunk) {
            return [
                "DateLastComment" => new RawExpression("COALESCE(DateLastComment, DateInserted)"),
                "hot" => new RawExpression("hot"),
            ];
        }

        return [];
    }

    /**
     * Recalculate aggregates for a discussion.
     *
     * @param array $discussion
     *
     * @return void
     */
    public function recalculateDiscussionAggregates(array $discussion): void
    {
        $discussionID = $discussion["DiscussionID"];
        $categoryID = $discussion["CategoryID"];
        $countComments = $this->db->createSql()->getCount("Comment", ["DiscussionID" => $discussionID]);
        $newFirstComment = $this->selectCommentFragment(
            [
                "DiscussionID" => $discussionID,
            ],
            "DateInserted"
        );
        $newLastComment = $this->selectCommentFragment(
            [
                "DiscussionID" => $discussionID,
            ],
            "-DateInserted"
        );
        $newDateLastComment = $newLastComment["DateInserted"] ?? $discussion["DateInserted"];

        $staticUpdates = [
            "CountComments" => $countComments,
            "DateLastComment" => $newDateLastComment,
            "FirstCommentID" => $newFirstComment["CommentID"] ?? null,
            "LastCommentID" => $newLastComment["CommentID"] ?? null,
            "LastCommentUserID" => $newLastComment["InsertUserID"] ?? null,
        ];
        $this->discussionModel->setField(
            $discussion["DiscussionID"],
            $this->getSinkOverrides($discussion, $newLastComment) + [
                    "hot" => $this->discussionModel->getHotCalculationExpression(),
                ] +
                $staticUpdates
        );

        $this->eventManager->fire("forumAggregateModel_commentInsert", [
            "comment" => $newLastComment,
            "discussion" => array_merge($discussion, $staticUpdates),
        ]);

        $this->categoryModel->counts("CountComments", ["CategoryID" => $categoryID]);
        $this->categoryModel->counts("CountDiscussions", ["CategoryID" => $categoryID]);
        $this->categoryModel->counts("CountAll");
        $this->scheduleRecentPostRefreshOnCategory($discussion["CategoryID"]);
    }

    /**
     * Handle aggregate updates when a comment is inserted.
     *
     * Updates the discussion:
     * - CountComments (increment)
     * - DateLastComment (set)
     * - hot
     * - LastCommentUserID (set)
     * - FirstCommentID
     * - LastCommentID
     *
     * Updates the category:
     *  - CountComments (increment)
     *  - CountAllComments (increment)
     *  - LastCategoryID (set)
     *  - LastDiscussionID (set)
     *  - LastCommentID (set)
     *
     * @param array $comment
     * @param array $discussion The categoryID that comment was created in.
     * @return void
     */
    public function handleCommentInsert(array $comment, array $discussion): void
    {
        $categoryID = $discussion["CategoryID"];
        $isLatestDiscussionComment = false;
        if (dateCompare($comment["DateInserted"], $discussion["DateLastComment"]) >= 0) {
            $isLatestDiscussionComment = true;
        }

        $sinkOverrides = $this->getSinkOverrides($discussion, $comment);
        $discussionUpdates = $sinkOverrides + [
            "CountComments" => new Increment(1),
            "FirstCommentID" => $this->getFirstCommentIDUpdateExpression($comment["DiscussionID"]),
        ];

        if ($isLatestDiscussionComment) {
            $discussionUpdates += [
                "DateLastComment" => $comment["DateInserted"],
                "LastCommentID" => $comment["CommentID"],
                "LastCommentUserID" => $comment["InsertUserID"],
            ];
        }
        // Hot needs to come after DateLastComment and CountComments because it uses the new values.
        $discussionUpdates + ["hot" => $this->discussionModel->getHotCalculationExpression()];
        $this->discussionModel->setField($comment["DiscussionID"], $discussionUpdates);

        $this->eventManager->fire("forumAggregateModel_comment", [
            "comment" => $comment,
            "discussion" => array_merge($discussion, ["CountComments" => $discussion["CountComments"] + 1]),
        ]);

        $category = \CategoryModel::instance()->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        if (!$category) {
            return;
        }

        $categoryUpdates = [
            "CountComments" => new Increment(1),
        ];
        $isLatestCategoryComment = false;
        if (
            !$category["LastDateInserted"] ||
            dateCompare($comment["DateInserted"], $category["LastDateInserted"]) >= 0
        ) {
            $isLatestCategoryComment = true;
        }

        if ($isLatestCategoryComment) {
            $categoryUpdates += [
                "LastDiscussionID" => $comment["DiscussionID"],
                "LastCommentID" => $comment["CommentID"],
                "LastDateInserted" => $comment["DateInserted"],
            ];
        }

        $this->categoryModel->setField($categoryID, $categoryUpdates);

        \CategoryModel::incrementAggregateCount($categoryID, \CategoryModel::AGGREGATE_COMMENT, 1);

        if ($isLatestCategoryComment) {
            $this->scheduleRecentPostRefreshOnCategory($categoryID);
            \CategoryModel::setAsLastCategory($categoryID);
        }
    }

    /**
     * Get a setExpression that fetches the first commentID for a discussion.
     *
     * @param int $discussionID
     * @return SetLiteral
     */
    private function getFirstCommentIDUpdateExpression(int $discussionID): SetLiteral
    {
        return new RawExpression(
            <<<SQL
(
SELECT CommentID FROM GDN_Comment
WHERE DiscussionID = {$this->db->quoteExpression($discussionID)}
ORDER BY DateInserted ASC
LIMIT 1
)
SQL
        );
    }

    /**
     *  Handle aggregate updates when a comment is moved.
     *
     *  Updates the new and old discussion:
     *  - CountComments (increment/decrement)
     *  - DateLastComment (recalculate)
     *  - hot
     *  - LastCommentUserID (recalculate)
     *
     *
     *  Updates the new and old category (if category changed):
     *   - CountComments
     *   - CountAllComments
     *   - LastCategoryID
     *   - LastCommentID
     *
     * @param array $comment
     * @param array $prevDiscussion
     * @param array $newDiscussion
     * @return void
     */
    public function handleCommentMove(array $comment, array $prevDiscussion, array $newDiscussion): void
    {
        $this->handleCommentDelete($comment, $prevDiscussion);
        $this->handleCommentInsert($comment, $newDiscussion);
    }

    /**
     *  Handle aggregate updates when a comment is deleted.
     *
     *  Updates the discussion:
     *  - CountComments (decrement)
     *  - DateLastComment (recalculated)
     *  - hot
     *  - LastCommentUserID (recalculated)
     *
     *  Updates the category:
     *   - CountComments (decrement)
     *   - CountAllComments (decrement)
     *   - LastCategoryID (recalculate)
     *   - LastCommentID (recalculate)
     *
     * @param array $comment
     * @param array $discussion
     * @return void
     */
    public function handleCommentDelete(array $comment, array $discussion): void
    {
        $newLastComment = $this->selectCommentFragment(
            [
                "DiscussionID" => $comment["DiscussionID"],
            ],
            "-DateInserted"
        );
        $newDateLastComment = $newLastComment["DateInserted"] ?? $discussion["DateInserted"];
        $this->discussionModel->setField($discussion["DiscussionID"], [
            "CountComments" => new Increment(-1),
            "DateLastComment" => $newDateLastComment,
            "hot" => $this->discussionModel->getHotCalculationExpression(),
            "FirstCommentID" => $this->getFirstCommentIDUpdateExpression($discussion["DiscussionID"]),
            "LastCommentID" => $newLastComment["CommentID"] ?? null,
            "LastCommentUserID" => $newLastComment["InsertUserID"] ?? null,
        ]);

        $this->eventManager->fire("forumAggregateModel_comment", [
            "comment" => $comment,
            "discussion" => array_merge($discussion, ["CountComments" => $discussion["CountComments"] - 1]),
        ]);

        $this->categoryModel->setField($discussion["CategoryID"], [
            "CountComments" => new Increment(-1),
        ]);

        \CategoryModel::decrementAggregateCount($discussion["CategoryID"], \CategoryModel::AGGREGATE_COMMENT, 1);
        $this->scheduleRecentPostRefreshOnCategory($discussion["CategoryID"]);
    }

    /**
     * Schedule a recalculation of the recent post for a category.
     * By deferring this we allow a bulk operation that moves a bunch of comments or discussion in 1 request
     * to only perform the recalculation at the end of the request.
     *
     * @param int $categoryID
     *
     * @return void
     */
    private function scheduleRecentPostRefreshOnCategory(int $categoryID): void
    {
        $needsSchedule = empty($this->deferredRecentPostUpdates);
        $this->deferredRecentPostUpdates[$categoryID] = true;
        if ($needsSchedule) {
            // Let's schedule it.
            $this->scheduler->addJobDescriptor(
                new NormalJobDescriptor(CallbackJob::class, [
                    "callback" => function () {
                        foreach ($this->deferredRecentPostUpdates as $categoryID => $_) {
                            $this->categoryModel->refreshAggregateRecentPost($categoryID, true);
                            unset($this->deferredRecentPostUpdates[$categoryID]);
                        }
                    },
                ])
            );
        }
    }

    /**
     * Select a comment fragment.
     *
     * @param array $where
     * @param string $orderBy
     *
     * @return array{CommentID: int, DateInserted: string, DiscussionID: int, InsertUserID: int}|null
     */
    private function selectCommentFragment(array $where, string $orderBy): ?array
    {
        // Not necessarilly the newest comment.
        // Fetch the newest one.
        $newLastestComment = $this->db
            ->createSql()
            ->select("CommentID, DateInserted, DiscussionID, InsertUserID")
            ->from("Comment")
            ->where($where)
            ->orderBy($orderBy)
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return $newLastestComment ?: null;
    }

    /**
     * Handle a discussion insert.
     *
     * - Increment category counts.
     * - Set LastPost information on the category.
     *
     * @param array $discussion
     * @return void
     */
    public function handleDiscussionInsert(array $discussion): void
    {
        $category = \CategoryModel::instance()->getID($discussion["CategoryID"], DATASET_TYPE_ARRAY);
        if (!$category) {
            return;
        }

        $isLatestCategoryDiscussion = false;
        if (
            !$category["LastDateInserted"] ||
            dateCompare($discussion["DateInserted"], $category["LastDateInserted"]) >= 0
        ) {
            $isLatestCategoryDiscussion = true;
        }

        $categoryUpdates = [
            "CountDiscussions" => new Increment(1),
            "CountComments" => new Increment($discussion["CountComments"]),
        ];

        if ($isLatestCategoryDiscussion) {
            $categoryUpdates += [
                "LastDiscussionID" => $discussion["DiscussionID"],
                "LastCommentID" => null,
                "LastDateInserted" => $discussion["DateInserted"],
            ];
            \CategoryModel::setAsLastCategory($discussion["CategoryID"]);
        }

        $this->categoryModel->setField($discussion["CategoryID"], $categoryUpdates);

        \CategoryModel::incrementAggregateCount($discussion["CategoryID"], \CategoryModel::AGGREGATE_DISCUSSION, 1);
        \CategoryModel::incrementAggregateCount(
            $discussion["CategoryID"],
            \CategoryModel::AGGREGATE_COMMENT,
            $discussion["CountComments"]
        );
        if ($isLatestCategoryDiscussion) {
            $this->scheduleRecentPostRefreshOnCategory($discussion["CategoryID"]);
        }
    }

    /**
     * Handle a discussion insert.
     *
     * - Increment/decrement category counts.
     * - Set LastPost information on the prev/new category.
     *
     * @param array $discussion
     * @param array $prevCategory
     * @param array $newCategory
     *
     * @return void
     */
    public function handleDiscussionMove(array $discussion, array $prevCategory, array $newCategory): void
    {
        $this->handleDiscussionDelete($discussion, $prevCategory["CategoryID"]);

        $this->handleDiscussionInsert(["CategoryID" => $newCategory["CategoryID"]] + $discussion);
    }

    /**
     * Handle a discussion being deleted.
     *
     * - Decrement category counts.
     * - Recalculate last post.
     *
     * @param array $discussion
     * @param int $categoryID
     *
     * @return void
     */
    public function handleDiscussionDelete(array $discussion, int $categoryID): void
    {
        $this->categoryModel->setField($categoryID, [
            "CountDiscussions" => new Increment(-1),
            "CountComments" => new Increment(-$discussion["CountComments"]),
        ]);

        \CategoryModel::decrementAggregateCount($categoryID, \CategoryModel::AGGREGATE_DISCUSSION, 1);
        \CategoryModel::decrementAggregateCount(
            $categoryID,
            \CategoryModel::AGGREGATE_COMMENT,
            $discussion["CountComments"]
        );

        $this->scheduleRecentPostRefreshOnCategory($categoryID);
    }
}
