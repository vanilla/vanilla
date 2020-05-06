/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { IEvent } from "@library/events/Event";
import { eventsClasses } from "@library/events/eventStyles";
import { Event } from "@library/events/Event";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import { EventAttendance } from "@library/events/EventAttendanceDropDown";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

export interface IEventList {
    headingLevel?: 2 | 3;
    data: IEvent[];
    hideIfEmpty?: boolean;
    emptyMessage?: string;
    compact?: boolean;
}

/**
 * Component for displaying the list of events
 */
export function EventsList(props: IEventList) {
    const classes = eventsClasses({
        compact: props.compact,
    });

    if (!props.data || props.data.length === 0) {
        const { hideIfEmpty = false, emptyMessage = t("This category does not have any events.") } = props;
        return hideIfEmpty ? null : <p className={classes.empty}>{emptyMessage}</p>;
    }
    const going = t("Going");
    const maybe = t("Maybe");

    const options = [
        { name: going, value: EventAttendance.GOING },
        { name: maybe, value: EventAttendance.MAYBE },
        { name: t("Not going"), value: EventAttendance.NOT_GOING },
    ] as ISelectBoxItem[];

    let longestCharCount = 0;
    if (props.compact) {
        if (going.length > maybe.length) {
            longestCharCount = going.length;
        } else {
            longestCharCount = maybe.length;
        }
    } else {
        options.forEach(o => {
            if (o.name && o.name.length > longestCharCount) {
                longestCharCount = o.name.length;
            }
        });
    }

    return (
        <>
            <ul className={classes.list}>
                {props.data.map((event, i) => {
                    return (
                        <Event
                            className={classNames({ isFirst: i === 0 })}
                            headingLevel={props.headingLevel}
                            {...event}
                            key={i}
                            longestCharCount={longestCharCount}
                            attendanceOptions={options}
                            compact={props.compact}
                        />
                    );
                })}
            </ul>
        </>
    );
}
