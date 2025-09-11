<?php
/**
 * User discussions model
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.3
 */

use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\PipelineModel;

/**
 * To keep track of who has seen a discussion, and when it was seen.
 */
class UserDiscussionModel extends PipelineModel
{
    /**
     * User Discussion Model constructor.
     *
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @throws Exception
     */
    public function __construct(private CommentModel $commentModel, private DiscussionModel $discussionModel)
    {
        parent::__construct("UserDiscussion");
    }

    /**
     * Set Watch for a discussion
     *
     * @param array $discussion
     * @param bool $recordDiscussionView
     *
     * @return array
     * @throws Exception
     */
    public function setWatch(int $discussionID): array
    {
        // Get the discussion data.
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (empty($discussion)) {
            return [];
        }

        $discussion = $this->discussionModel->normalizeRow($discussion);

        $userID = Gdn::session()->UserID;

        $countWatch = $discussion["countComments"];
        [$dateLastViewed, $op] = $this->calculateWatch($discussion, $discussion["countComments"]);

        switch ($op) {
            case "update":
                $this->update(
                    [
                        "CountComments" => $countWatch,
                        "DateLastViewed" => $dateLastViewed,
                    ],
                    [
                        "UserID" => $userID,
                        "DiscussionID" => $discussion["discussionID"],
                    ]
                );
                break;
            case "insert":
                // Insert watch data.
                $this->insert(
                    [
                        "UserID" => $userID,
                        "DiscussionID" => $discussion["discussionID"],
                        "CountComments" => $countWatch,
                        "DateLastViewed" => $dateLastViewed,
                    ],
                    ["Ignore" => true]
                );
                break;
        }

        // If there is a discrepancy between $countWatch and $discussion->CountCommentWatch,
        // update CountCommentWatch with the correct value.
        $discussion["countCommentWatch"] = $countWatch;
        return $discussion;
    }

    /**
     * Decide whether to update a record, insert a new record, or do nothing.
     *
     * @param array $discussion Discussion being watched.
     * @param int $totalComments Total in entire discussion (hard limit).
     * @return array Returns a 3-item array of types int, string|null, string|null.
     * @throws Exception Throws an exception if given an invalid timestamp.
     */
    public function calculateWatch(array $discussion, int $totalComments)
    {
        $newComments = false;
        $latestComment = $this->commentModel
            ->getWhere(["DiscussionID" => $discussion["discussionID"]], "dateInserted", "desc", 1)
            ->firstRow(DATASET_TYPE_ARRAY);

        // If the discussion doesn't have any comments, use the date the discussion started.
        $maxDateInserted = $latestComment["DateInserted"] ?? $discussion["dateInserted"];

        // This discussion looks familiar...
        if (
            $discussion["countCommentWatch"] > 0 ||
            !empty($discussion["watchUserID"] ?? null) ||
            $discussion["bookmarked"]
        ) {
            if (isset($discussion["dateLastViewed"])) {
                $newComments |=
                    Gdn_Format::toTimestamp($discussion["dateLastComment"]) >
                    Gdn_Format::toTimestamp($discussion["dateLastViewed"]);
            }

            if (
                $totalComments > $discussion["countCommentWatch"] ||
                $totalComments != $discussion["countCommentWatch"] ||
                is_null($discussion["dateLastViewed"])
            ) {
                $newComments = true;
            }

            $operation = $newComments ? "update" : null;
        } else {
            $operation = "insert";
        }

        $dateLastViewed = DiscussionModel::maxDate($discussion["dateLastViewed"], $maxDateInserted);

        return [$dateLastViewed, $operation];
    }
}
