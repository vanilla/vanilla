/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { IEvent } from "@library/events/Event";
import { DataList } from "@library/dataLists/dataList";
import { IUserFragment } from "@library/@types/api/users";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { t } from "@vanilla/i18n/src";
import { IEventAttendance } from "@library/events/EventAttendanceDropDown";

/**
 * Component for displaying an event details
 */
export function EventAttendanceAsRadio(props: IEventAttendance) {
    const classes = eventsClasses();
    return (
        <div className={classes.attendanceAsRadio}>
            <></>
        </div>
    );
}
