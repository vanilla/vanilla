<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use CommentModel;
use Garden\Web\Exception\NotFoundException;
use LogModel;
use Vanilla\Models\Model;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model for delete comment together.
 */
class CommentDeleteModel implements SystemCallableInterface
{
    const COMMENT_DELETE_TOMBSTONE = "tombstone";
    const COMMENT_DELETE_FULL = "full";
    private const OPT_DELETE_SINGLE_TRANSACTION_ID = "deleteSingleTransactionID";

    /**
     * DI.
     *
     * @param CommentModel $commentModel
     * @param \Gdn_Database $database
     * @param CommentThreadModel $commentThreadModel
     */
    public function __construct(
        private CommentModel $commentModel,
        private CommentThreadModel $commentThreadModel,
        private \Gdn_Database $database
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["deleteCommentsIterator"];
    }

    /**
     * Get long runner count of total items to process.
     *
     * @param array $commentIDs The IDs of the comments to delete.
     *
     * @return int
     */
    public function getTotalCount(array $commentIDs): int
    {
        return count($commentIDs);
    }

    /**
     * Iterator for merging a list of discussions together.
     *
     * @param array $commentIDs The IDs of the comments to be deleted.
     * @param string $deleteMethod full or tombstone delete method.
     * @param array $options Options for deletion.
     *
     * @return \Generator A long runner generator.
     */
    public function deleteCommentsIterator(array $commentIDs, string $deleteMethod, array $options = []): \Generator
    {
        if (empty($commentIDs)) {
            throw new NotFoundException("Comment", ["commentID" => $commentIDs]);
        }

        // Report how much progress is possible.
        yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$commentIDs]);

        $commentIDs = array_unique($commentIDs);

        // Loop through the sources.
        $completedCommentIDs = [];
        $sourceCommentIterator = $this->commentModel->getWhereIterator(
            ["CommentID" => $commentIDs, "c.parentRecordType" => $this->commentModel->getParentRecordTypes()],
            "CommentID",
            "asc"
        );
        foreach ($sourceCommentIterator as $commentID => $comment) {
            try {
                if ($deleteMethod === self::COMMENT_DELETE_TOMBSTONE) {
                    unset($options[self::OPT_DELETE_SINGLE_TRANSACTION_ID]);
                    $this->commentModel->tombstoneDeleteID($commentID);
                } elseif ($comment["countChildComments"] === 0) {
                    unset($options[self::OPT_DELETE_SINGLE_TRANSACTION_ID]);
                    $this->commentModel->deleteID($commentID);
                } elseif ($deleteMethod === self::COMMENT_DELETE_FULL && $comment["countChildComments"] > 0) {
                    foreach ($this->deleteNestedCommentIterator($commentID, $comment["DiscussionID"], $options) as $_) {
                    }
                    $this->commentModel->deleteID($commentID, $options);
                    LogModel::endTransaction();
                    // These transaction IDs are forwarded only for a single discussion.
                    unset($options[self::OPT_DELETE_SINGLE_TRANSACTION_ID]);
                }

                // We were successful! Track progress.
                $completedCommentIDs[] = $commentID;
                yield new LongRunnerSuccessID($commentID);
            } catch (LongRunnerTimeoutException $e) {
                // Ran out of time, prepare for the next call.
                $remainingCommentIDs = array_diff($commentIDs, $completedCommentIDs);
                $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] = LogModel::getTransactionID();
                return new LongRunnerNextArgs([$remainingCommentIDs, $deleteMethod, $options]);
            } catch (\Exception $e) {
                // Failed to delete that comment.
                yield new LongRunnerFailedID($commentID, $e);
            }
        }
    }

    /**
     * Merge one discussion into another.
     *
     * @param array $commentID The parent nested comment to delete.
     * @param array $options options for deletion
     *
     * @return \Generator
     */
    private function deleteNestedCommentIterator(int $commentID, int $discussionID, array $options = []): \Generator
    {
        $commentStructure = $this->commentThreadModel->selectCommentThreadStructure(
            ["parentCommentID" => $commentID, "parentRecordID" => $discussionID, "parentRecordType" => "discussion"],
            [
                Model::OPT_OFFSET => 0,
                Model::OPT_LIMIT => 10000,
                CommentThreadModel::OPT_THREAD_STRUCTURE => new CommentThreadStructureOptions(
                    1000,
                    collapseChildLimit: 10000,
                    focusCommentID: $commentID
                ),
            ]
        );
        $commentIDs = $commentStructure->getPreloadCommentIDs();
        // Delete from the bottom up.
        $sourceCommentIterator = $this->commentModel->getWhereIterator(["CommentID" => $commentIDs], "depth", "desc");
        // Start up a common log transaction to tie all the deleted items together.
        $logTransactionID = $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] ?? null;
        $transactionID = LogModel::beginTransaction($logTransactionID);
        // Make sure if we get stopped while iterating, the next call will have the same transactionID.
        $options[self::OPT_DELETE_SINGLE_TRANSACTION_ID] = $transactionID;
        $options["parentCommentDelete"] = false;
        foreach ($sourceCommentIterator as $childCommentID => $childComment) {
            $this->commentModel->deleteID($childCommentID, $options);
            try {
                // Yield for the generator in case we hit a timeout.
                yield;
            } catch (LongRunnerTimeoutException $e) {
                return new LongRunnerNextArgs([$commentID, $discussionID, $options]);
            }
        }
    }
}
