<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use DiscussionModel;
use Gdn_Database;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Class for expanding discussion fragments on notifications.
 */
class DiscussionNotificationExpander
{
    public function __construct(private Gdn_Database $db, private DiscussionModel $discussionModel)
    {
    }

    /**
     * @param array $notificationOrNotifications An notifications API item or array of them. Items are mutated in place.
     * @return void
     */
    public function expandDiscussions(array &$notificationOrNotifications): void
    {
        if (ArrayUtils::isAssociative($notificationOrNotifications)) {
            // If a single notification is passed, we need to expand its discussion data.
            $notifications = [&$notificationOrNotifications];
        } else {
            // If multiple notifications are passed, we expand each one.
            $notifications = &$notificationOrNotifications;
        }

        // First pass, collect commentIDs and normalize discussion IDs.
        $commentIDs = [];
        foreach ($notifications as &$notificationInitial) {
            $recordType = $notificationInitial["recordType"] ?? null;
            $recordID = $notificationInitial["recordID"] ?? null;
            if ($recordType === null || $recordID === null) {
                $notificationInitial["discussionID"] = null;
                continue;
            }
            switch (strtolower($recordType)) {
                case "discussion":
                    $notificationInitial["discussionID"] = $recordID;
                    break;
                case "comment":
                    $notificationInitial["discussionID"] = null;
                    $commentIDs[] = $recordID;
                    break;
                default:
                    $notificationInitial["discussionID"] = null;
                    break;
            }
        }

        if (!empty($commentIDs)) {
            // Get discussionIDs for these comments.
            $commentRows = $this->db
                ->createSql()
                ->from("Comment")
                ->select("CommentID, parentRecordID")
                ->where("CommentID", $commentIDs)
                ->where("parentRecordType", "discussion")
                ->get()
                ->resultArray();

            $commentDiscussionIDs = array_column($commentRows, "parentRecordID", "CommentID");

            // Loop back through notifications and set discussionID for comments.
            foreach ($notifications as &$notification) {
                $recordType = $notification["recordType"] ?? null;
                $recordID = $notification["recordID"] ?? null;
                if ($recordType === null || $recordID === null) {
                    continue;
                }

                if (strtolower($recordType) === "comment" && isset($commentDiscussionIDs[$recordID])) {
                    $notification["discussionID"] = $commentDiscussionIDs[$recordID];
                }
            }
        }

        // Now final pass, join the discussion fragments.
        ModelUtils::leftJoin($notifications, ["discussionID" => "discussion"], function (array $discussionIDs) {
            $discussionRows = $this->db
                ->createSql()
                ->from("Discussion")
                ->select("*")
                ->where("DiscussionID", $discussionIDs)
                ->get()
                ->resultArray();

            $resultsByDiscussionID = [];
            foreach ($discussionRows as $row) {
                $resultsByDiscussionID[$row["DiscussionID"]] = [
                    "discussionID" => $row["DiscussionID"],
                    "name" => $row["Name"],
                    "url" => DiscussionModel::discussionUrl($row),
                    "categoryID" => $row["CategoryID"],
                ];
            }
            return $resultsByDiscussionID;
        });
    }
}
