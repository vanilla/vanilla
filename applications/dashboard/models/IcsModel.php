<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Gdn;
use Ramsey\Uuid\Uuid;

/**
 * Model for generating ICS files.
 */
class IcsModel
{
    /**
     * Create an ICS file from event data. See https://icalendar.org/ for more information about the file format.
     *
     * @param array $eventData
     * @return string
     */
    public function createIcsFile(array $eventData): string
    {
        // wrangle the data into the format we need
        $uuid = Uuid::uuid4()->toString();
        $startDate = gmdate("Ymd\THis\Z", strtotime($eventData["DateStarts"]));
        $endDate = gmdate("Ymd\THis\Z", strtotime($eventData["DateEnds"]));
        $body = Gdn::formatService()->renderPlainText($eventData["Body"], $eventData["Format"]);
        $location = $eventData["LocationUrl"] ?? ($eventData["Location"] ?? "");
        $ctaUrl = $eventData["CtaUrl"] ?? "";
        $organizerID = $eventData["DisplayOrganizer"] ? $eventData["InsertUserID"] : null;
        if ($organizerID !== null) {
            $organizer = Gdn::userModel()->getID($organizerID, DATASET_TYPE_ARRAY);
            $organizerEmail = $organizer["Email"];
            $organizerName = $organizer["Name"];
        }
        $organizer = $organizerID !== null ? "CN={$organizerName}:mailto:{$organizerEmail}" : "";

        // populate the template
        $file = <<<FILE
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Vanilla Community Event//
BEGIN:VEVENT
SUMMARY:{$eventData["Name"]}
DTSTART:{$startDate}
DTEND:{$endDate}
UID:{$uuid}
LOCATION:{$location}
DESCRIPTION:{$body}
URL:{$ctaUrl}
ORGANIZER;{$organizer}
END:VEVENT
END:VCALENDAR
FILE;
        return $file;
    }
}
