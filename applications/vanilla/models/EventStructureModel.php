<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Gdn_DatabaseStructure;

class EventStructureModel
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
            ->table("Event")
            ->primaryKey("EventID")
            ->column("Name", "varchar(255)")
            ->column("Body", "mediumtext", true)
            ->column("Format", "varchar(10)", true)
            ->column("ParentRecordType", "varchar(25)", true, "index.Event")
            ->column("ParentRecordID", "int", true, "index.Event")
            ->column("DateStarts", "datetime", false, "index.DateStart")
            ->column("DateEnds", "datetime", true, "index.DateEnd")
            ->column("AllDayEvent", "tinyint", "0")
            ->column("Location", "varchar(255)", true)
            ->column("DateInserted", "datetime")
            ->column("InsertUserID", "int") // organizer
            ->column("DateUpdated", "datetime", true)
            ->column("UpdateUserID", "int", true)
            ->column("GroupID", "int", true, "key") // eventually make events stand-alone.
            ->column("LocationUrl", "varchar(255)", true)
            ->column("DisplayOrganizer", "tinyint", 1)
            ->column("CtaLabel", "varchar(255)", true)
            ->column("CtaUrl", "varchar(255)", true)
            ->column("ForeignType", "varchar(255)", true)
            ->column("ForeignSubtype", "varchar(255)", true)
            ->column("ForeignID", "varchar(255)", true)
            ->column("NotifiedUpdate", "tinyint", 0)
            ->column("NotifiedReminder", "tinyint", 0)
            ->column("countComments", "int", 0)
            ->column("dateLastComment", "datetime", true)
            ->column("lastCommentID", "int", true)
            ->column("lastCommentUserID", "int", true)
            ->column("publishedSilently", "tinyint", 0)
            ->set();

        $structure
            ->table("UserEvent")
            ->column("EventID", "int", false, ["primary", "index.Attending"])
            ->column("UserID", "int", false, ["primary", "key", "index.Attending"])
            ->column("DateInserted", "datetime")
            ->column(
                "Attending",
                ["Yes", "No", "Maybe", "Invited", "Interested", "Registered", "Unregistered"],
                "Invited",
                "index.Attending"
            )
            ->column("ForeignType", "varchar(255)", true)
            ->column("ForeignUserID", "varchar(255)", true)
            ->set();
    }
}
