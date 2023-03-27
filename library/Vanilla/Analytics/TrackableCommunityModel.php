<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use CategoryModel;
use CommentModel;
use DiscussionModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Utility functions for Trackable Events.
 */
class TrackableCommunityModel
{
    /** @var DiscussionModel */
    private $discussionModel;

    /** @var TrackableUserModel */
    private $userUtils;

    /** @var CommentModel */
    private $commentModel;

    /**
     * DI.
     *
     * @param DiscussionModel $discussionModel
     * @param TrackableUserModel $userUtils
     * @param CommentModel $commentModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        TrackableUserModel $userUtils,
        CommentModel $commentModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->userUtils = $userUtils;
        $this->commentModel = $commentModel;
    }

    /**
     * Grab basic information about a category, based on a category ID.
     *
     * @param int $categoryID The target category's integer ID.
     * @return array An array representing basic category data.
     */
    public function getTrackableCategory(int $categoryID): array
    {
        $categoryDetails = CategoryModel::categories($categoryID);
        if ($categoryDetails) {
            $category = [
                "categoryID" => $categoryDetails["CategoryID"],
                "name" => $categoryDetails["Name"],
                "slug" => $categoryDetails["UrlCode"],
                "url" => categoryUrl($categoryDetails),
            ];
        } else {
            // Fallback category data
            $category = ["categoryID" => 0];
            trigger_error(
                "Tried to fetch trackable category but it didn't exist. CategoryID: $categoryID",
                E_USER_WARNING
            );
        }

        return $category;
    }

    /**
     * Fetch all ancestors up to, and including, the current category.
     *
     * @param int $categoryID ID of the category we're tracking down the ancestors of.
     * @return array An array of objects containing the ID and name of each of the category's ancestors.
     */
    public static function getCategoryAncestors(int $categoryID): array
    {
        $ancestors = [];

        // Grab our category's ancestors, which include the current category.
        $categories = CategoryModel::getAncestors($categoryID, false, true);

        $categoryLevel = 0;
        foreach ($categories as $currentCategory) {
            $categoryLabel = "cat" . sprintf("%02d", ++$categoryLevel);

            $ancestors[$categoryLabel] = [
                "categoryID" => (int) $currentCategory["CategoryID"],
                "name" => $currentCategory["Name"],
                "slug" => $currentCategory["UrlCode"],
            ];
        }

        return $ancestors;
    }

    /**
     * Get a discussion with special fields used for tracking.
     *
     * @param int|array $discussionOrDiscussionID
     * @return array
     */
    public function getTrackableDiscussion($discussionOrDiscussionID): array
    {
        if (is_int($discussionOrDiscussionID)) {
            $discussion = $this->discussionModel->getID($discussionOrDiscussionID, DATASET_TYPE_ARRAY);
            if (empty($discussion)) {
                return [
                    "discussionID" => 0,
                ];
            }
            $discussion = $this->discussionModel->normalizeRow($discussion);
        } else {
            $discussion = $discussionOrDiscussionID;
        }
        $schema = $this->discussionModel->schema();
        $firstCommentID = $discussion["firstCommentID"] ?? ($discussion["FirstCommentID"] ?? null);
        $discussion = $schema->validate($discussion);

        // Tracking events don't need the body. It takes up a lot of space unnecessarily.
        $discussion["body"] = null;

        $discussion["discussionType"] = ucfirst($discussion["type"]);
        $discussion["firstCommentID"] = $firstCommentID;
        $discussion["category"] = $this->getTrackableCategory($discussion["categoryID"]);
        $discussion["categoryAncestors"] = self::getCategoryAncestors($discussion["categoryID"]);
        $discussion["groupID"] = $discussion["groupID"] ?? null;
        $discussion["commentMetric"] = [
            "firstComment" => false,
            "time" => null,
        ];
        $discussion["countComments"] = (int) $discussion["countComments"];
        $discussion["dateInserted"] = TrackableDateUtils::getDateTime($discussion["dateInserted"]);
        $discussion["discussionUser"] = $this->userUtils->getTrackableUser($discussion["insertUserID"]);

        return $discussion;
    }

    /**
     * Add special fields for tracking to data for a discussion that has no ID (as happens, e.g., when a posted
     * discussion is immediately flagged as spam before being posted).
     *
     * @param array $discussionData
     * @return array
     */
    public function getTrackableLogDiscussion(array $discussionData): array
    {
        $discussionData["discussionID"] = 0;
        $discussionData = ArrayUtils::camelCase($discussionData);
        $schema = $this->discussionModel->schema();
        $discussionData = $schema->validate($discussionData, true);
        $discussionData["discussionType"] = ucfirst($discussionData["type"] ?? "Discussion");
        $discussionData["firstCommentID"] = null;
        $discussionData["category"] = $this->getTrackableCategory($discussionData["categoryID"]);
        $discussionData["categoryAncestors"] = self::getCategoryAncestors($discussionData["categoryID"]);
        $discussionData["groupID"] = $discussionData["groupID"] ?? null;
        $discussionData["commentMetric"] = [
            "firstComment" => false,
            "time" => null,
        ];
        $discussionData["dateInserted"] = TrackableDateUtils::getDateTime($discussionData["dateInserted"]);
        $discussionData["discussionUser"] = $this->userUtils->getTrackableUser($discussionData["insertUserID"]);
        // Tracking events don't need the body. It takes up a lot of space unnecessarily.
        $discussionData["body"] = null;
        return $discussionData;
    }

    /**
     * Grab standard data for a comment.
     *
     * @param int|array $commentOrCommentID A comment's unique ID, used to query data.
     * @param string $type Event type (e.g. comment_add or comment_edit).
     * @return array Array representing comment row on success, false on failure.
     */
    public function getTrackableComment($commentOrCommentID, string $type = "comment_add"): array
    {
        if (is_numeric($commentOrCommentID)) {
            $comment = $this->commentModel->getID($commentOrCommentID, DATASET_TYPE_ARRAY);
            if (empty($comment)) {
                return [
                    "commentID" => 0,
                ];
            }
            $comment = $this->commentModel->normalizeRow($comment);
        } else {
            $comment = $commentOrCommentID;
        }

        $commentSchema = $this->commentModel->schema();
        $comment = $commentSchema->validate($comment);

        // The body is large and unnecessary.
        $comment["body"] = null;

        $data = [
            "commentID" => (int) $comment["commentID"],
            "dateInserted" => TrackableDateUtils::getDateTime($comment["dateInserted"]),
            "discussionID" => (int) $comment["discussionID"],
            "insertUser" => $this->userUtils->getTrackableUser($comment["insertUserID"]),
        ];

        try {
            $discussion = $this->getTrackableDiscussion($comment["discussionID"]);
        } catch (\Exception $ex) {
            $discussion = false;
        }

        if ($discussion) {
            $commentNumber = val("countComments", $discussion, 0);

            $data["category"] = val("category", $discussion);
            $data["categoryAncestors"] = val("categoryAncestors", $discussion);
            $data["discussionUser"] = val("discussionUser", $discussion);

            $timeSinceDiscussion = $data["dateInserted"]["timestamp"] - $discussion["dateInserted"]["timestamp"];
            $data["commentMetric"] = [
                "firstComment" => $data["commentID"] === $discussion["firstCommentID"] ? true : false,
                "time" => (int) $timeSinceDiscussion,
            ];

            // The count of comments we get from the discussion doesn't include this one, so we compensate if it's an add.
            if ($type === "comment_add") {
                $commentPosition = $commentNumber;
                $data["commentPosition"] = $commentPosition;
            }

            // If it's a delete, decrement the countComments number.
            if ($type === "comment_delete") {
                $data["commentPosition"] = 0;
            }

            if ($type === "comment_edit") {
                $data["commentPosition"] = $commentNumber;
            }

            // Removing those redundancies...
            unset(
                $discussion["category"],
                $discussion["categoryAncestors"],
                $discussion["commentMetric"],
                $discussion["discussionUser"],
                $discussion["record"]
            );

            $data["discussion"] = $discussion;
        }

        return $data;
    }

    /**
     * Add special fields for tracking to data for a comment that has no ID (as happens, e.g., when a posted
     * discussion is immediately flagged as spam before being posted).
     *
     * @param array $commentData
     * @return array
     */
    public function getTrackableLogComment(array $commentData): array
    {
        $commentData["commentID"] = 0;
        $commentData = ArrayUtils::camelCase($commentData);
        $schema = $this->commentModel->schema();
        $commentData = $schema->validate($commentData, true);
        $commentData["dateInserted"] = TrackableDateUtils::getDateTime($commentData["dateInserted"]);
        $commentData["discussionID"] = (int) $commentData["discussionID"];
        $commentData["insertUser"] = $this->userUtils->getTrackableUser($commentData["insertUserID"]);
        try {
            $discussion = $this->getTrackableDiscussion($commentData["discussionID"]);
        } catch (\Exception $ex) {
            $discussion = false;
        }
        if ($discussion) {
            $commentData["category"] = $discussion["category"];
            $commentData["categoryAncestors"] = $discussion["categoryAncestors"];
            $commentData["discussionUser"] = $discussion["discussionUser"];

            // Removing those redundancies...
            unset(
                $discussion["category"],
                $discussion["categoryAncestors"],
                $discussion["commentMetric"],
                $discussion["discussionUser"],
                $discussion["record"]
            );
        }

        // The body is large and unnecessary.
        $commentData["body"] = null;

        return $commentData;
    }
}
