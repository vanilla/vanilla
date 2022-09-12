<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model for merging discussions together.
 */
class DiscussionMergeModel implements SystemCallableInterface
{
    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var \CommentModel */
    private $commentModel;

    /** @var \Gdn_Database */
    private $database;

    /**
     * DI.
     *
     * @param \DiscussionModel $discussionModel
     * @param \CommentModel $commentModel
     * @param \Gdn_Database $database
     */
    public function __construct(\DiscussionModel $discussionModel, \CommentModel $commentModel, \Gdn_Database $database)
    {
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->database = $database;
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["mergeDiscussionsIterator"];
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
     * @param array $discussionIDs The IDs of the discussions to merge into the destinationDiscussionID.
     * @param int $destinationDiscussionID The discussionID to merge into.
     * @param bool $addRedirects Preserve the sourceDiscussions as redirects to the target discussion.
     *
     * @return \Generator A long runner generator.
     */
    public function mergeDiscussionsIterator(
        array $discussionIDs,
        int $destinationDiscussionID,
        bool $addRedirects
    ): \Generator {
        $sourceDiscussionIDs = array_diff($discussionIDs, [$destinationDiscussionID]);
        // Fetch the target discussion.
        $destinationDiscussion = $this->discussionModel->getID($destinationDiscussionID, DATASET_TYPE_ARRAY);
        if (empty($destinationDiscussion)) {
            throw new NotFoundException("Discussion", ["discussionID" => $destinationDiscussionID]);
        }

        // Validate the target discussion.
        $this->validateMergeableDiscussionType($destinationDiscussion);

        // Report how much progress is possible.
        yield new LongRunnerQuantityTotal(
            [$this, "getTotalCount"],
            [$discussionIDs, $destinationDiscussionID, $addRedirects]
        );

        $sourceDiscussionIDs = array_unique($sourceDiscussionIDs);

        // Loop throught the sources.
        $completedDiscussionIDs = [];
        $sourceDiscussionIterator = $this->discussionModel->getWhereIterator(["DiscussionID" => $sourceDiscussionIDs]);
        foreach ($sourceDiscussionIterator as $sourceDiscussionID => $sourceDiscussion) {
            try {
                // Merge the individual discussion, yielding along the way.
                foreach (
                    $this->mergeDiscussionIntoIterator($sourceDiscussion, $destinationDiscussion, $addRedirects)
                    as $_
                ) {
                    yield;
                }

                // We were successful! Track progress.
                $completedDiscussionIDs[] = $sourceDiscussionID;
                yield new LongRunnerSuccessID($sourceDiscussionID);
            } catch (LongRunnerTimeoutException $e) {
                // Ran out of time, prepare for the next call.
                $remainingDiscussionIDs = array_diff($discussionIDs, $completedDiscussionIDs);
                return new LongRunnerNextArgs([$remainingDiscussionIDs, $destinationDiscussionID, $addRedirects]);
            } catch (\Exception $e) {
                // Failed to merge that discussion.
                yield new LongRunnerFailedID($sourceDiscussionID, $e);
            }
        }
    }

    /**
     * Merge one discussion into another.
     *
     * @param array $sourceDiscussion The source discussion.
     * @param array $destinationDiscussion The target discussion.
     * @param bool $addRedirects Preserve the sourceDiscussion as a redirect to the target discussion.
     *
     * @return \Generator
     */
    private function mergeDiscussionIntoIterator(
        array $sourceDiscussion,
        array $destinationDiscussion,
        bool $addRedirects
    ): \Generator {
        $this->validateMergeableDiscussionType($sourceDiscussion);

        // Validate our IDs.
        $destinationDiscussionID = $destinationDiscussion["DiscussionID"];
        $sourceDiscussionID = $sourceDiscussion["DiscussionID"];
        if ($destinationDiscussionID === $sourceDiscussionID) {
            throw new ClientException("Can't merge a discussion into itself.", [
                "sourceDiscussionID" => $sourceDiscussionID,
                "destinationDiscussionID" => $destinationDiscussionID,
            ]);
        }

        // Move the comments first.
        foreach ($this->moveCommentsIterator($sourceDiscussionID, $destinationDiscussionID) as $_) {
            yield;
        }

        // Convert the discussion into a comment.
        $this->convertDiscussionToComment($sourceDiscussion, $destinationDiscussion, $addRedirects);

        // Dispatch a Discussion event (merge)
        $senderUserID = Gdn::session()->UserID;
        $sender = $senderUserID ? Gdn::userModel()->getFragmentByID($senderUserID) : null;
        $discussionEvent = $this->discussionModel->eventFromRow(
            $sourceDiscussion,
            DiscussionEvent::ACTION_MERGE,
            $sender
        );
        $discussionEvent->setDestinationDiscussionID($destinationDiscussionID);
        $this->discussionModel->getEventManager()->dispatch($discussionEvent);
    }

    /**
     * Validate that a discussion can be merged.
     *
     * @param array $discussion The full discussion record.
     * @param array|null $typeAllowList Narrow down a list of allowed types.
     */
    private function validateMergeableDiscussionType(array $discussion, ?array $typeAllowList = null)
    {
        $typeBlockList = ["redirect"];
        $discussionType = strtolower($discussion["Type"] ?? "Discussion");
        $errorMessage = "Can't merge discussion type '$discussionType'";
        $errorContext = [
            "discussionID" => $discussion["DiscussionID"],
            "discussionType" => $discussionType,
            "allowList" => $typeAllowList,
            "blockList" => $typeBlockList,
        ];
        if (!empty($typeAllowList) && !in_array($discussionType, array_map("strtolower", $typeAllowList))) {
            throw new ClientException($errorMessage, 400, $errorContext);
        }

        if (!empty($typeBlockList) && in_array($discussionType, array_map("strtolower", $typeBlockList))) {
            throw new ClientException($errorMessage, 400, $errorContext);
        }
    }

    /**
     * Convert a discussion into a comment.
     *
     * @param array $sourceDiscussion The source discussion.
     * @param array $destinationDiscussion The target discussion.
     * @param bool $addRedirect Preserve the sourceDiscussion as a redirect to the target discussion.
     */
    private function convertDiscussionToComment(
        array $sourceDiscussion,
        array $destinationDiscussion,
        bool $addRedirect
    ) {
        // Once every comment has been moved to the new discussion, we create a comment out of the 'old' discussion.
        $comment = arrayTranslate($sourceDiscussion, [
            "Body",
            "Format",
            "DateInserted",
            "InsertUserID",
            "InsertIPAddress",
            "DateUpdated",
            "UpdateUserID",
            "UpdateIPAddress",
            "Attributes",
            "Spam",
            "Likes",
            "Abuse",
        ]);
        $comment["DiscussionID"] = $destinationDiscussion["DiscussionID"];
        $commentID = $this->commentModel->save($comment);
        ModelUtils::validationResultToValidationException($this->commentModel);
        $comment["CommentID"] = $commentID;

        // This is an old event and handlers expect a pluggable, so we have to use the legacy event firing system.
        $eventSource = $this->discussionModel;
        $eventSource->EventArguments["SourceDiscussion"] = $sourceDiscussion;
        $eventSource->EventArguments["DestinationDiscussion"] = $destinationDiscussion;
        $eventSource->EventArguments["TargetComment"] = $comment;
        $eventSource->fireEvent("transformDiscussionToComment");

        if ($addRedirect) {
            // Transform the discussion into a redirect.
            $this->convertDiscussionToRedirect($sourceDiscussion, \CommentModel::commentUrl($comment));
            ModelUtils::validationResultToValidationException($this->discussionModel);
        } else {
            // Other-wise delete it.
            ModelUtils::consumeGenerator(
                $this->discussionModel->deleteDiscussionIterator($sourceDiscussion["DiscussionID"])
            );
        }
    }

    /**
     * Convert a discussion into a redirect.
     *
     * @param array $discussion The discussion to convert.
     * @param string $redirectUrl The URL to redirect to.
     */
    private function convertDiscussionToRedirect(array $discussion, string $redirectUrl)
    {
        $this->discussionModel->defineSchema();
        $maxNameLength = $this->discussionModel->Schema->getField("Name")->Length;

        $modifiedDiscussion = [
            "DiscussionID" => $discussion["DiscussionID"],
            "Name" => sliceString(sprintf(t("Merged: %s"), $discussion["Name"]), $maxNameLength),
            "Type" => "redirect",
            "Body" => formatString(t('This discussion has been <a href="{url,html}">merged</a>.'), [
                "url" => $redirectUrl,
            ]),
            "Format" => "Html",
            "Closed" => true,
        ];

        $this->discussionModel->save($modifiedDiscussion);
        ModelUtils::validationResultToValidationException($this->discussionModel);
    }

    /**
     * Get an iterator that moves comments from a source discussion into a target discussion.
     * Comments are moved one at time.
     *
     * @param int $sourceDiscussionID
     * @param int $destinationDiscussionID
     *
     * @return \Generator
     */
    private function moveCommentsIterator(int $sourceDiscussionID, int $destinationDiscussionID): \Generator
    {
        $sourceCommentIDs = $this->database
            ->createSql()
            ->select("CommentID")
            ->where("DiscussionID", $sourceDiscussionID)
            ->get("Comment")
            ->column("CommentID");

        foreach ($sourceCommentIDs as $sourceCommentID) {
            $this->commentModel->save([
                "CommentID" => $sourceCommentID,
                "DiscussionID" => $destinationDiscussionID,
            ]);
            ModelUtils::validationResultToValidationException($this->commentModel);
            yield;
        }
    }
}
