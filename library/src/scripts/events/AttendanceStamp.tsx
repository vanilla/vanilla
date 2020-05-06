/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import classNames from "classnames";
import { EventAttendance } from "@library/events/eventOptions";

/**
 * Component for displaying your attendance to an event
 */
export function AttendanceStamp(props: { attendance?: EventAttendance; className?: string }) {
    const classes = eventsClasses();
    if (!props.attendance) {
        return null;
    }
    return (
        <div
            className={classNames(classes.attendanceStamp, classes.attendanceClass(props.attendance), props.className)}
        >
            {props.attendance}
        </div>
    );
}
