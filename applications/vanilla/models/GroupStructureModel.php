<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Gdn_DatabaseStructure;

class GroupStructureModel
{
    /**
     * Structure our database schema.
     *
     * @param Gdn_DatabaseStructure $structure
     *
     * @return void
     * @throws Exception
     */
    public static function structure(Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("Group")
            ->primaryKey("GroupID")
            ->column("Name", "varchar(150)", false, "unique")
            ->column("Description", "text")
            ->column("Format", "varchar(10)", true)
            ->column("CategoryID", "int", false, "key")
            ->column("Icon", "varchar(255)", true)
            ->column("Banner", "varchar(255)", true)
            ->column("Privacy", ["Public", "Private", "Secret"], "Public")
            ->column("Registration", ["Public", "Approval", "Invite"], true) // deprecated
            ->column("Visibility", ["Public", "Members"], true) // deprecated
            ->column("CountMembers", "uint", "0")
            ->column("CountDiscussions", "uint", "0")
            ->column("DateLastComment", "datetime", true)
            ->column("LastCommentID", "int", null)
            ->column("LastDiscussionID", "int", null)
            ->column("DateInserted", "datetime")
            ->column("InsertUserID", "int")
            ->column("DateUpdated", "datetime", true)
            ->column("UpdateUserID", "int", true)
            ->column("Attributes", "text", true)
            ->set();

        $structure
            ->table("UserGroup")
            ->primaryKey("UserGroupID")
            ->column("GroupID", "int", false, "unique")
            ->column("UserID", "int", false, ["unique", "key"])
            ->column("DateInserted", "datetime")
            ->column("InsertUserID", "int")
            ->column("Role", ["Leader", "Member"])
            ->column("Followed", "tinyint", 0)
            ->column("DateFollowed", "datetime", true)
            ->column("DateUnFollowed", "datetime", true)
            ->column("NotificationDiscussionPopup", "tinyint", 0)
            ->column("NotificationDiscussionEmail", "tinyint", 0)
            ->column("NotificationCommentPopup", "tinyint", 0)
            ->column("NotificationCommentEmail", "tinyint", 0)
            ->column("NotificationEventPopup", "tinyint", 0)
            ->column("NotificationEventEmail", "tinyint", 0)
            ->column("NotificationAnnouncementPopup", "tinyint", 0)
            ->column("NotificationAnnouncementEmail", "tinyint", 0)
            ->column("Notification", "tinyint", 0)
            ->column("DigestEnabled", "tinyint", 0)
            ->set();

        $structure
            ->table("GroupApplicant")
            ->primaryKey("GroupApplicantID")
            ->column("GroupID", "int", false, "unique")
            ->column("UserID", "int", true, ["unique", "key"])
            ->column("Email", "varchar(50)", true, ["unique", "key"])
            ->column("Type", ["Application", "Invitation", "Denied", "Banned"])
            ->column("Reason", "varchar(200)", true) // reason for wanting to join.
            ->column("DateInserted", "datetime")
            ->column("InsertUserID", "int")
            ->column("DateUpdated", "datetime", true)
            ->column("UpdateUserID", "int", true)
            ->set();
    }
}
