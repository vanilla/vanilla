<?php
/**
 * @author Pavel Goncharov <pavelgoncharov@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Gdn;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model for merging discussions together.
 */
class DiscussionSplitModel implements SystemCallableInterface
{
    /**
     * DI.
     *
     * @param \DiscussionModel $discussionModel
     * @param \CommentModel $commentModel
     * @param CommentThreadModel $threadModel
     */
    public function __construct(
        private \DiscussionModel $discussionModel,
        private \CommentModel $commentModel,
        private CommentThreadModel $threadModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["splitDiscussionsIterator"];
    }

    /**
     * Get long runner count of total items to process.
     *
     * @param array $discussionIDs The IDs of the discussions to merge into the destinationDiscussionID.
     * @param int $destinationDiscussionID The discussionID to merge into.
     * @param bool $addRedirects Preserve the sourceDiscussions as redirects to the target discussion.
     *
     * @return int
     */
    public function getTotalCount(array $discussionIDs, int $destinationDiscussionID, bool $addRedirects): int
    {
        $sourceDiscussionIDs = array_diff($discussionIDs, [$destinationDiscussionID]);
        $sourceDiscussionIDs = array_unique($sourceDiscussionIDs);
        return count($sourceDiscussionIDs);
    }

    /**
     * Iterator for merging a list of discussions together.
     *
     * @param array $commentIDs The IDs of the comments to split into the destinationDiscussionData.
     * @param int $destinationDiscussionID The discussion split into.
     * @param int $sourceDiscussionID The discussion split from.
     * @parem int $depth Nested comment depth to start.
     *
     * @return \Generator A long runner generator.
     */
    public function splitDiscussionsIterator(
        array $commentIDs,
        int $destinationDiscussionID,
        int $sourceDiscussionID,
        int $depth = 1
    ): \Generator {
        $completedCommentIDs = [];

        yield new LongRunnerQuantityTotal(function () use ($commentIDs) {
            return count($commentIDs);
        });
        $maxDepth = $this->commentModel->getMaxDepthFromCommentIDs($commentIDs);

        // Loop through the comments.
        while ($depth <= $maxDepth) {
            $commentsIterator = $this->commentModel->getWhereIterator(
                [
                    "CommentID" => $commentIDs,
                    "Depth" => $depth,
                    "DiscussionID" => $sourceDiscussionID,
                    "countChildComments>" => 0,
                ],
                "CommentID",
                "asc"
            );
            foreach ($commentsIterator as $commentID => $comment) {
                try {
                    // move comment to new discussion.
                    $this->threadModel->updateChildrenRecursively($commentID, [
                        "DiscussionID" => $destinationDiscussionID,
                        "parentRecordID" => $destinationDiscussionID,
                    ]);
                    $this->commentModel->SQL
                        ->update("Comment")
                        ->set([
                            "parentCommentID" => null,
                            "depth" => 1,
                        ])
                        ->where(["CommentID" => $commentID, "DiscussionID" => $destinationDiscussionID])
                        ->put();
                    // We were successful! Track progress.
                    $completedCommentIDs[] = $commentID;
                    yield new LongRunnerSuccessID($commentID);
                } catch (LongRunnerTimeoutException $e) {
                    // Ran out of time, prepare for the next call.
                    $remainingCommentIDs = array_diff($commentIDs, $completedCommentIDs);
                    return new LongRunnerNextArgs([
                        $remainingCommentIDs,
                        $destinationDiscussionID,
                        $sourceDiscussionID,
                        $depth,
                    ]);
                } catch (\Exception $e) {
                    // Failed to split in that comment.
                    yield new LongRunnerFailedID($commentID, $e);
                }
            }
            $depth++;
        }
        $this->commentModel->SQL
            ->update("Comment")
            ->set([
                "DiscussionID" => $destinationDiscussionID,
                "parentRecordID" => $destinationDiscussionID,
                "parentCommentID" => null,
                "depth" => 1,
            ])
            ->where(["CommentID" => $commentIDs, "DiscussionID" => $sourceDiscussionID])
            ->put();

        yield new LongRunnerSuccessID(count(array_diff($commentIDs, $completedCommentIDs)));
        $aggregateModel = Gdn::getContainer()->get(ForumAggregateModel::class);
        $aggregateModel->recalculateDiscussionAggregates(
            $this->discussionModel->getID($sourceDiscussionID, DATASET_TYPE_ARRAY)
        );
        $destinationDiscussion = $this->discussionModel->getID($destinationDiscussionID, DATASET_TYPE_ARRAY);
        $aggregateModel->recalculateDiscussionAggregates($destinationDiscussion);

        $senderUserID = $destinationDiscussion["InsertUserID"];
        $senderFragment = $senderUserID ? Gdn::userModel()->getFragmentByID($senderUserID) : null;

        $discussionEvent = $this->discussionModel->eventFromRow(
            $destinationDiscussion,
            DiscussionEvent::ACTION_SPLIT,
            $senderFragment
        );
        $discussionEvent->setSourceDiscussionID($sourceDiscussionID);
        $discussionEvent->setDestinationDiscussionID($destinationDiscussionID);
        $discussionEvent->setCommentIDs($commentIDs);
        $this->discussionModel->getEventManager()->dispatch($discussionEvent);
    }

    public function recalculateDepth(int $discussionID)
    {
    }
}
