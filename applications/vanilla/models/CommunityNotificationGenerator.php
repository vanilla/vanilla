<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use ActivityModel;
use CategoryModel;
use CommentModel;
use DiscussionModel;
use Exception;
use Gdn;
use Gdn_Database;
use Generator;
use PermissionModel;
use PermissionNotificationGenerator;
use Ramsey\Uuid\Uuid;
use UserModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerMultiAction;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Class for processing post notifications.
 */
class CommunityNotificationGenerator implements SystemCallableInterface
{
    const CONF_NOTIFICATIONS_CHUNK_SIZE = "categoryFollowing.notifications.chunkSize";

    /**
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @param ActivityModel $activityModel
     * @param ConfigurationInterface $configuration
     * @param UserModel $userModel
     * @param LongRunner $longRunner
     * @param Gdn_Database $database
     * @param PermissionModel $permissionModel
     */
    public function __construct(
        private CommentModel $commentModel,
        private DiscussionModel $discussionModel,
        private ActivityModel $activityModel,
        private ConfigurationInterface $configuration,
        private UserModel $userModel,
        private LongRunner $longRunner,
        private Gdn_Database $database,
        private PermissionModel $permissionModel
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["processNotifications", "processExpensiveNotifications", "categoryNotificationsIterator"];
    }

    /**
     * Notify the relevant users after a new discussion has been posted.
     *
     * @param array $discussion The discussion record in question.
     * @throws Exception
     */
    public function notifyNewDiscussion(array $discussion)
    {
        $categoryID = $discussion["CategoryID"];
        $discussionID = $discussion["DiscussionID"];
        $body = $discussion["Body"];
        $format = $discussion["Format"];
        $insertUserID = $discussion["InsertUserID"];
        $name = $discussion["Name"];
        $type = $discussion["Type"] ?? "Discussion";

        $discussionCategory = CategoryModel::categories($categoryID);
        if ($discussionCategory === null) {
            ErrorLogger::warning(
                "Attempted to send notification for a discussion, but it's category did not exist.",
                ["notifications"],
                [
                    "discussionID" => $discussionID,
                    "categoryID" => $categoryID,
                ]
            );
            return;
        }
        $categoryName = $discussionCategory["Name"] ?? null;

        if (strtolower($type) !== "discussion") {
            $code = "HeadlineFormat.Discussion.{$type}";
        } else {
            $code = "HeadlineFormat.Discussion";
        }

        $activity = [
            "ActivityType" => "Discussion",
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "ActivityUserID" => $insertUserID,
            "HeadlineFormat" => t(
                $code,
                '{ActivityUserID,user} started a new discussion: <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "RecordType" => "Discussion",
            "RecordID" => $discussionID,
            "Route" => discussionUrl($discussion, "", "/"),
            "Data" => [
                "Name" => $name,
                "Category" => $categoryName,
                "Reason" => "advanced",
            ],
            "Ext" => [
                "Email" => [
                    "Format" => $format,
                    "Story" => $body,
                ],
            ],
        ];

        if (!$this->configuration->get("Vanilla.Email.FullPost")) {
            $activity["Ext"]["Email"] = $this->activityModel->setStoryExcerpt($activity["Ext"]["Email"]);
        }

        $mentions = $this->getMentions($body, $format);

        $eventArguments = [
            "Activity" => &$activity,
            "MentionedUsers" => $mentions,
        ];

        $isValid = $this->fireDiscussionBeforeNotificationEvent($discussion, $eventArguments);

        if (!$isValid) {
            ErrorLogger::warning(
                "Attempted to send notification for a discussion, but it failed validation.",
                ["notifications"],
                [
                    "discussionID" => $discussionID,
                    "categoryID" => $categoryID,
                    "eventArguments" => $eventArguments,
                ]
            );
            return;
        }
        $actions = [];
        $actions[] = $this->processMentionNotifications($activity, $discussion, $mentions);

        $activity["Data"]["Reason"] = "advanced";
        $actions[] = new LongRunnerAction(PermissionNotificationGenerator::class, "notificationGenerator", [
            $activity,
            "discussions.view",
            "NewDiscussion" . "." . $categoryID,
            0,
            "Category",
            $categoryID,
        ]);
        $actions = array_values(array_filter($actions));

        $finalAction = count($actions) === 1 ? $actions[0] : new LongRunnerMultiAction($actions);
        $this->longRunner->runDeferred($finalAction);
    }

    /**
     * Notify the relevant users when a new comment has been posted.
     *
     * @param array $comment A full comment record.
     * @param array $discussion A full discussion record.
     */
    public function notifyNewComment(array $comment, array $discussion)
    {
        $commentID = $comment["CommentID"];
        $discussionID = $discussion["DiscussionID"];
        $categoryID = $discussion["CategoryID"];

        $category = CategoryModel::categories($categoryID);
        if ($category === null) {
            ErrorLogger::warning(
                "Attempted to send notification for a comment, but it's category did not exist.",
                ["notifications"],
                [
                    "commentID" => $commentID,
                    "discussionID" => $discussionID,
                    "categoryID" => $categoryID,
                ]
            );
            return;
        }

        $body = $comment["Body"] ?? null;
        $format = $comment["Format"] ?? null;

        // Prepare the notification queue.
        $activity = [
            "ActivityType" => "Comment",
            "ActivityUserID" => $comment["InsertUserID"] ?? null,
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "HeadlineFormat" => t(
                "HeadlineFormat.Comment",
                '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "PluralHeadlineFormat" => t(
                "PluralHeadlineFormat.Comment",
                'There are <strong>{count}</strong> new comments on discussion: <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "RecordType" => "Comment",
            "RecordID" => $commentID,
            "ParentRecordID" => $discussionID,
            "Route" => "/discussion/comment/{$commentID}#Comment_{$commentID}",
            "Data" => [
                "Name" => $discussion["Name"] ?? null,
                "Category" => $category["Name"] ?? null,
            ],
            "Ext" => [
                "Email" => [
                    "Format" => $format,
                    "Story" => $body,
                ],
            ],
        ];

        // Pass generic activity to events.
        $mentions = $this->getMentions($body, $format);
        $eventArguments = [
            "Activity" => &$activity,
            "MentionedUsers" => $mentions,
        ];

        // Throw an event for users to add their own events.
        $isValid = $this->fireCommentBeforeNotificationEvent($comment, $discussion, $eventArguments);

        if (!$isValid) {
            return;
        }

        if (!$this->configuration->get("Vanilla.Email.FullPost")) {
            $activity["Ext"]["Email"] = $this->activityModel->setStoryExcerpt($activity["Ext"]["Email"]);
        }

        $actions = [];
        $actions[] = $this->processMentionNotifications($activity, $discussion, $mentions);
        $actions[] = $this->processMineNotifications($activity, $discussion);
        $actions[] = $this->processParticipatedNotifications($activity, $discussion);
        $actions[] = $this->processBookmarkNotifications($activity, $discussion);
        $activity["Data"]["Reason"] = "advanced";
        $actions[] = new LongRunnerAction(PermissionNotificationGenerator::class, "notificationGenerator", [
            $activity,
            "discussions.view",
            "NewComment" . "." . $categoryID,
            0,
            "Category",
            $categoryID,
        ]);
        $actions = array_values(array_filter($actions));

        $multiAction = new LongRunnerMultiAction($actions);
        $this->longRunner->runDeferred($multiAction);
    }

    /**
     * Create user-specific notifications and send them to the activity model queue.
     *
     * @param array $notificationData
     * @param array $discussion
     * @return LongRunnerAction|null
     */
    public function processBookmarkNotifications(array $notificationData, array $discussion): ?LongRunnerAction
    {
        $discussionID = $discussion["DiscussionID"] ?? null;

        $groupData = [
            "notifyUsersWhere" => [
                "Bookmarked" => true,
            ],
            "preference" => "BookmarkComment",
        ];

        return new LongRunnerAction(self::class, "processExpensiveNotifications", [
            $notificationData,
            "bookmark",
            $groupData,
            $discussionID,
        ]);
    }

    /**
     * Process "mine"-type notifications.
     *
     * @param array $notificationData
     * @param array $discussion
     * @return LongRunnerAction
     */
    public function processMineNotifications(array $notificationData, array $discussion): LongRunnerAction
    {
        $discussionUserID = $discussion["InsertUserID"] ?? null;
        $groupData = [
            "notifyUserIDs" => [$discussionUserID],
            "preference" => "DiscussionComment",
        ];

        return new LongRunnerAction(self::class, "processNotifications", [
            $notificationData,
            "mine",
            $groupData,
            $discussion["DiscussionID"],
        ]);
    }

    /**
     * Process "participated"-type notifications.
     *
     * @param array $notificationData
     * @param array $discussion
     * @return ?LongRunnerAction
     */
    public function processParticipatedNotifications(array $notificationData, array $discussion): ?LongRunnerAction
    {
        $groupData = [
            "notifyUsersWhere" => [
                "Participated" => true,
            ],
            "preference" => "ParticipateComment",
        ];

        return new LongRunnerAction(self::class, "processExpensiveNotifications", [
            $notificationData,
            "participated",
            $groupData,
            $discussion["DiscussionID"],
        ]);
    }

    /**
     * Get mention usernames for an activity.
     *
     * @param $body
     * @param $format
     * @return array
     */
    private function getMentions($body, $format): array
    {
        if (!is_string($body) || !is_string($format)) {
            return [];
        } else {
            return Gdn::formatService()->parseMentions($body, $format);
        }
    }

    /**
     * Process "mention"-type notifications.
     *
     * @param array $notificationData
     * @param array $discussion
     * @param array|null $mentions
     * @return LongRunnerAction|null
     */
    public function processMentionNotifications(
        array $notificationData,
        array $discussion,
        array $mentions
    ): ?LongRunnerAction {
        if (empty($mentions)) {
            return null;
        }
        $notificationData["ActivityType"] =
            $notificationData["RecordType"] === "Comment" ? "CommentMention" : "DiscussionMention";
        $groupData = [
            "headlineFormat" => t(
                "HeadlineFormat.Mention",
                '{ActivityUserID,user} mentioned you in <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "notifyUserIDs" => [],
            "preference" => "Mention",
        ];

        // This is a kludge to remove the ParentRecordID from mention notifications, as we don't want them batched.
        unset($notificationData["ParentRecordID"]);
        unset($notificationData["PluralHeadline"]);

        foreach ($mentions as $mentionName) {
            $mentionUser = $this->userModel->getByUsername($mentionName);
            if ($mentionUser) {
                $groupData["notifyUserIDs"][] = $mentionUser->UserID ?? null;
            }
        }

        return new LongRunnerAction(self::class, "processNotifications", [
            $notificationData,
            "mention",
            $groupData,
            $discussion["DiscussionID"],
        ]);
    }

    /**
     * @param array $comment The full comment record.
     * @param array $discussion The discussion record.
     * @param array $eventArguments Data for the event.
     *
     * @return bool Whether this is a valid notification.
     */
    private function fireCommentBeforeNotificationEvent(array $comment, array $discussion, array &$eventArguments): bool
    {
        $isValid = true;
        $eventArguments["Comment"] = $comment;
        $eventArguments["Discussion"] = $discussion;
        $eventArguments["NotifiedUsers"] = array_keys(ActivityModel::$Queue);
        $eventArguments["UserModel"] = $this->userModel;
        $eventArguments["IsValid"] = &$isValid;
        $eventArguments["ActivityModel"] = $this->activityModel;
        Gdn::eventManager()->fire("commentModel_beforeNotification", $this->commentModel, $eventArguments);
        return $isValid;
    }

    /**
     * @param array $discussion The discussion record.
     * @param array $eventArguments Arguments for the event.
     *
     * @return bool Is notification valid.
     */
    private function fireDiscussionBeforeNotificationEvent(array $discussion, array &$eventArguments): bool
    {
        $isValid = true;
        $eventArguments["Discussion"] = $discussion;
        $eventArguments["UserModel"] = $this->userModel;
        $eventArguments["IsValid"] = &$isValid;
        $eventArguments["ActivityModel"] = $this->activityModel;
        Gdn::eventManager()->fire("discussionModel_beforeNotification", $this->discussionModel, $eventArguments);
        return $isValid;
    }

    /**
     * Process notifications.
     *
     * @param array $notificationData
     * @param string $reason
     * @param array $groupData
     * @param int $discussionID
     * @return Generator
     */
    public function processNotifications(
        array $notificationData,
        string $reason,
        array $groupData,
        int $discussionID,
        ?int $maxNotifiedUserID = null
    ): Generator {
        $headlineFormat = $groupData["headlineFormat"] ?? $notificationData["HeadlineFormat"];
        $notifyUserIDs = $groupData["notifyUserIDs"] ?? [];
        $preference = $groupData["preference"] ?? false;
        $options = $groupData["options"] ?? [];
        $options["DisableFloodControl"] = true;

        $notifyUserIDs = array_unique(
            array_filter($notifyUserIDs, function ($userID) use ($notificationData) {
                return $userID != $notificationData["ActivityUserID"];
            })
        );

        yield new LongRunnerQuantityTotal(function () use ($notifyUserIDs) {
            return count($notifyUserIDs ?? []);
        });

        sort($notifyUserIDs);

        foreach ($notifyUserIDs as $notifyUserID) {
            try {
                if ($notifyUserID <= $maxNotifiedUserID) {
                    continue;
                }

                // Check user can still see the discussion.
                if (!$this->discussionModel->canView($discussionID, $notifyUserID)) {
                    continue;
                }

                $notification = $notificationData;
                $notification["HeadlineFormat"] = $headlineFormat;
                $notification["NotifyUserID"] = $notifyUserID;
                $notification["Data"]["Reason"] = $reason;
                $notification["Data"]["Preference"] = $preference;
                $result = $this->activityModel->save($notification, $preference, $options);

                $longRunnerID = "{$notificationData["RecordType"]}_{$notification["RecordID"]}_User_{$notifyUserID}_NotificationType_{$reason}";
                $maxNotifiedUserID = $notifyUserID;

                if (!$this->didActivitySave($result)) {
                    yield new LongRunnerFailedID($longRunnerID);
                } else {
                    yield new LongRunnerSuccessID($longRunnerID);
                }
            } catch (LongRunnerTimeoutException $timeoutException) {
                return new LongRunnerNextArgs([
                    $notificationData,
                    $reason,
                    $groupData,
                    $discussionID,
                    $maxNotifiedUserID,
                ]);
            } catch (Exception $e) {
                yield new LongRunnerFailedID(
                    "{$notificationData["RecordType"]}_{$notification["RecordID"]}_User_{$notifyUserID}_NotificationType_{$reason}"
                );
            }
        }
    }

    /**
     * Process notifications that could have a lot of users to be notified.
     *
     * @param array $notificationData
     * @param string $reason
     * @param array $groupData
     * @param int $discussionID
     * @param int|null $maxNotifiedUserID
     * @return Generator
     */
    public function processExpensiveNotifications(
        array $notificationData,
        string $reason,
        array $groupData,
        int $discussionID,
        ?int $maxNotifiedUserID = null
    ): Generator {
        $headlineFormat = $groupData["headlineFormat"] ?? $notificationData["HeadlineFormat"];
        $notifyUsersWhere = $groupData["notifyUsersWhere"] ?? [];
        $preference = $groupData["preference"] ?? false;
        $options = $groupData["options"] ?? [];
        $options["DisableFloodControl"] = true;

        $notifyUsersWhere["UserID <>"] = $notificationData["ActivityUserID"];

        if (!isset($notifyUsersWhere)) {
            return LongRunner::FINISHED;
        }

        yield new LongRunnerQuantityTotal(function () use ($discussionID, $notifyUsersWhere) {
            return $this->getNotifyUsersTotal($discussionID, $notifyUsersWhere);
        });

        $notifyUserIDs = $this->getNotifyUsersIterator($discussionID, $notifyUsersWhere);

        foreach ($notifyUserIDs as $notifyUserID) {
            if ($notifyUserID <= $maxNotifiedUserID) {
                continue;
            }
            try {
                if ($notifyUserID <= $maxNotifiedUserID) {
                    continue;
                }

                // Check user can still see the discussion.
                if (!$this->discussionModel->canView($discussionID, $notifyUserID)) {
                    continue;
                }

                $notification = $notificationData;
                $notification["HeadlineFormat"] = $headlineFormat;
                $notification["NotifyUserID"] = $notifyUserID;
                $notification["Data"]["Reason"] = $reason;
                $notification["Data"]["Preference"] = $preference;
                $result = $this->activityModel->save($notification, $preference, $options);
                $longRunnerID = "{$notificationData["RecordType"]}_{$notification["RecordID"]}_User_{$notifyUserID}_NotificationType_{$reason}";
                $maxNotifiedUserID = $notifyUserID;

                if (!$this->didActivitySave($result)) {
                    yield new LongRunnerFailedID($longRunnerID);
                } else {
                    yield new LongRunnerSuccessID($longRunnerID);
                }
            } catch (LongRunnerTimeoutException $timeoutException) {
                return new LongRunnerNextArgs([
                    $notificationData,
                    $reason,
                    $groupData,
                    $discussionID,
                    $maxNotifiedUserID,
                ]);
            } catch (Exception $e) {
                yield new LongRunnerFailedID(
                    "{$notificationData["RecordType"]}_{$notification["RecordID"]}_User_{$notifyUserID}_NotificationType_{$reason}"
                );
            }
        }
    }

    private function getNotifyUsersTotal(int $discussionID, array $where): int
    {
        $count = $this->database
            ->createSql()
            ->select("")
            ->select("COUNT(*)")
            ->from("UserDiscussion")
            ->where("DiscussionID", $discussionID)
            ->where($where)
            ->get()
            ->resultArray()[0]["COUNT(*)"];

        return $count;
    }

    private function getNotifyUsersIterator(int $discussionID, array $where, int $maxNotifiedUserID = 0): Generator
    {
        while (true) {
            $userIDs = $this->database
                ->createSql()
                ->select("UserID")
                ->from("UserDiscussion")
                ->where("DiscussionID", $discussionID)
                ->where($where)
                ->where("UserID >", $maxNotifiedUserID)
                ->limit(100)
                ->orderBy("UserID", "ASC")
                ->get()
                ->column("UserID");

            if (empty($userIDs)) {
                // No more left.
                return;
            }

            foreach ($userIDs as $userID) {
                yield $userID;
                $maxNotifiedUserID = $userID;
            }
        }
    }

    /**
     * @param int $discussionID
     * @return int|null
     */
    private function getCategoryIDForDiscussion(int $discussionID): ?int
    {
        $categoryID =
            $this->discussionModel
                ->createSql()
                ->select("CategoryID")
                ->from("Discussion")
                ->where("DiscussionID", $discussionID)
                ->get()
                ->firstRow()->CategoryID ?? null;
        return $categoryID;
    }

    /**
     * Check if an activity was saved.
     *
     * @param mixed $activityResult
     * @return bool
     */
    private function didActivitySave($activityResult): bool
    {
        return is_array($activityResult) && isset($activityResult["ActivityID"]);
    }
}
