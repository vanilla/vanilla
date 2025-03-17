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
use DiscussionStatusModel;
use Exception;
use Garden\Container\ContainerException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Community\Events\SubscriptionChangeEvent;
use Vanilla\Community\Events\TrackableDiscussionAnalyticsEvent;
use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Logger;
use Vanilla\Utility\ArrayUtils;

/**
 * Utility functions for Trackable Events.
 */
class TrackableCommunityModel implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var TrackableUserModel */
    private $userUtils;

    /** @var CommentModel */
    private $commentModel;

    private DiscussionStatusModel $discussionStatusModel;

    /**
     * DI.
     *
     * @param DiscussionModel $discussionModel
     * @param DiscussionStatusModel $discussionStatusModel
     * @param TrackableUserModel $userUtils
     * @param CommentModel $commentModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        DiscussionStatusModel $discussionStatusModel,
        TrackableUserModel $userUtils,
        CommentModel $commentModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->discussionStatusModel = $discussionStatusModel;
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

        $discussion["statusName"] =
            $this->discussionStatusModel->tryGetStatusFragment($discussion["statusID"])["name"] ?? "";
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
        $eventManager = \Gdn::eventManager();
        $eventManager->dispatch(new TrackableDiscussionAnalyticsEvent($discussion));

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
        $discussionData["announce"] = (bool) ($discussionData["announce"] ?? 0);
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
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     * @throws FormatterNotFoundException
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
            "insertUser" => $this->userUtils->getTrackableUser($comment["insertUserID"]),
        ];

        $parentHandler = $this->commentModel->getParentHandler($comment["parentRecordType"]);
        $data["category"] = $parentHandler->getCategoryID($comment["parentRecordID"]);

        try {
            $parentRecord = $parentHandler->getTrackableData($comment["parentRecordID"]);
        } catch (Exception $ex) {
            $parentRecord = false;
        }

        if ($parentRecord) {
            $commentNumber = $parentRecord["countComments"] ?? 0;
            $timeSinceDiscussion = $data["dateInserted"]["timestamp"] - $parentRecord["dateInserted"]["timestamp"];

            $data["category"] = val("category", $parentRecord);
            $data["categoryAncestors"] = val("categoryAncestors", $parentRecord);

            $data["commentMetric"] = [
                "time" => (int) $timeSinceDiscussion,
            ];

            // We only track the first comment for Discussions.
            if (isset($parentRecord["firstCommentID"])) {
                $data["commentMetric"]["firstComment"] = $parentRecord["firstCommentID"] === $comment["commentID"];
            }

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

            // We use the `discussionID`, and `userDiscussion` keys for backward compatibility.
            // The fields are in the catalog t`Parent Record ID`, and `Parent Record User` respectively.
            $data["discussionID"] = (int) $comment["parentRecordID"];
            $data["discussionUser"] = $this->userUtils->getTrackableUser($parentRecord["insertUserID"]);

            $data["parentRecordType"] = $comment["parentRecordType"];
            $data[$parentHandler->getRecordType()] = $parentRecord;
        }

        return $data;
    }

    /**
     * Add special fields for tracking to data for a comment that has no ID (as happens, e.g., when a posted
     * discussion is immediately flagged as spam before being posted).
     *
     * @param array $commentData
     * @return array
     * @throws ContainerException
     * @throws ValidationException
     * @throws NotFoundException
     */
    public function getTrackableLogComment(array $commentData): array
    {
        try {
            $commentData["commentID"] = 0;
            $commentData = ArrayUtils::camelCase($commentData);
            $schema = $this->commentModel->schema();
            $commentData = $schema->validate($commentData, true);
            $commentData["dateInserted"] = TrackableDateUtils::getDateTime($commentData["dateInserted"]);
            $commentData["insertUser"] = $this->userUtils->getTrackableUser($commentData["insertUserID"]);

            if (isset($commentData["parentRecordType"], $commentData["parentRecordID"])) {
                $parentRecord = $this->getParentRecord(
                    $commentData["parentRecordID"],
                    $commentData["parentRecordType"]
                );
            } else {
                $parentRecord = false;
            }

            if ($parentRecord) {
                $commentData["category"] = val("category", $parentRecord);
                $commentData["categoryAncestors"] = val("categoryAncestors", $parentRecord);
                $commentData["discussionUser"] = $this->userUtils->getTrackableUser($parentRecord["insertUserID"]);
            }

            // The body is large and unnecessary.
            $commentData["body"] = null;
        } catch (Exception $ex) {
            $this->logger->error("Error getting trackable log comment: " . $ex->getMessage());
            $commentData = [];
        }

        return $commentData;
    }

    /**
     * Get the parent record for a comment if it exists. Returns false if it doesn't.
     *
     * @param int $parentRecordID
     * @param string $parentRecordType
     * @return array|false
     * @throws NotFoundException
     */
    private function getParentRecord(int $parentRecordID, string $parentRecordType): array|false
    {
        $parentHandler = $this->commentModel->getParentHandler($parentRecordType);
        try {
            $parentRecord = $parentHandler->getTrackableData($parentRecordID);
        } catch (Exception $ex) {
            $parentRecord = false;
        }

        // There are no parent records for comments.
        if (empty($parentRecord)) {
            return false;
        }

        return $parentRecord;
    }

    /**
     * Add and modify fields for tag tracking data.
     *
     * @param array $tagData
     * @return array
     */
    public function getTrackableTag(array $tagData): array
    {
        $tagData["dateInserted"] = TrackableDateUtils::getDateTime($tagData["dateInserted"]);
        $insertUserID = $tagData["insertUserID"] ?? null;
        if (isset($insertUserID)) {
            $tagData["insertUser"] = $this->userUtils->getTrackableUser($tagData["insertUserID"]);
        }

        return $tagData;
    }

    /**
     * Add and modify fields for recordTag tracking data.
     *
     * @param array $recordTagData
     * @return array
     */
    public function getTrackableRecordTag(array $recordTagData): array
    {
        $dateInserted = $recordTagData["dateInserted"] ?? CurrentTimeStamp::getDateTime();
        $recordTagData["dateInserted"] = TrackableDateUtils::getDateTime($dateInserted);
        $recordTagData["insertUser"] = $this->userUtils->getTrackableUser($recordTagData["insertUserID"]);

        return $recordTagData;
    }

    /**
     * provide trackable data for category subscription
     *
     * @param array $categorySubscriptionData
     * @return array
     */
    public function getTrackableCategorySubscription(array $categorySubscriptionData): array
    {
        $data = [
            "follower" => $this->userUtils->getTrackableUser($categorySubscriptionData["user"]["userID"]),
            "category" => $this->getTrackableCategory($categorySubscriptionData["category"]["CategoryID"]),
        ];
        $data["category"]["totalFollowedCount"] = $categorySubscriptionData["category"]["totalFollowedCount"] ?? 0;
        $data["category"]["totalDigestCount"] = $categorySubscriptionData["category"]["totalDigestCount"] ?? 0;
        $type = $categorySubscriptionData["type"];
        $enabled = str_contains($categorySubscriptionData["subscription"], "Enabled");
        $data["dateTime"] = TrackableDateUtils::getDateTime(
            in_array($type, [SubscriptionChangeEvent::ACTION_FOLLOW, SubscriptionChangeEvent::ACTION_UNFOLLOW]) &&
            !$enabled
                ? $categorySubscriptionData["category"]["DateUnFollowed"]
                : $categorySubscriptionData["category"]["DateFollowed"]
        );
        $data["type"] = $type;
        $data["subscription"] = $categorySubscriptionData["subscription"];
        $data["enabled"] = $enabled;
        return $data;
    }

    /**
     * @param array $preferences
     * @return array
     */
    protected function normalizePreferences(array $preferences): array
    {
        $trackablePreferences["followed"] = $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_FOLLOW];
        $trackablePreferences["emailDigest"] =
            $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST] ?? false;
        $trackablePreferences["inAppDiscussions"] =
            $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_APP];
        $trackablePreferences["emailDiscussions"] =
            $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_DISCUSSION_EMAIL];
        $trackablePreferences["inAppComments"] = $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_APP];
        $trackablePreferences["emailComments"] =
            $preferences[\CategoriesApiController::OUTPUT_PREFERENCE_COMMENT_EMAIL];

        return $trackablePreferences;
    }
}
