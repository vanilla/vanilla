import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { EventAttendance } from "@library/events/EventAttendanceDropDown";
import classNames from "classnames";

export function AttendanceStamp(props: { attendance: EventAttendance }) {
    const classes = eventsClasses();
    return (
        <div className={classNames(classes.attendanceStamp, classes.attendanceClass(props.attendance))}>
            {props.attendance}
        </div>
    );
}
