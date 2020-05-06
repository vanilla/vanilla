/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { EventAttendance } from "@library/events/EventAttendanceDropDown";
import classNames from "classnames";

/**
 * Component for displaying your attendance to an event
 */
export function AttendanceStamp(props: { attendance: EventAttendance }) {
    const classes = eventsClasses();
    return (
        <div className={classNames(classes.attendanceStamp, classes.attendanceClass(props.attendance))}>
            {props.attendance}
        </div>
    );
}
