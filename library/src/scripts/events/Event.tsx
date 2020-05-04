import React from "react";
import DateTime, { DateFormats, IDateTime } from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { eventsClasses, eventsVariables } from "@library/events/eventStyles";
import Paragraph from "@library/layout/Paragraph";
import TruncatedText from "@library/content/TruncatedText";
import EventAttendanceDropDown, { EventAttendance } from "@library/events/EventAttendanceDropDown";
import classNames from "classnames";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { calc } from "csx";

interface IEventDate extends Omit<IDateTime, "mode" | "type"> {}

export interface IEvent {
    date: IEventDate;
    name: string;
    excerpt?: string;
    location: string;
    url: string;
    headingLevel?: 2 | 3;
    attendance: EventAttendance;
    className?: string;
    longestCharCount?: number; // for dynamic width, based on language
    attendanceOptions: ISelectBoxItem[];
}

/**
 * Component for displaying an accessible nicely formatted time string.
 */
export function Event(props: IEvent) {
    const classes = eventsClasses();

    const HeadingTag = (props.headingLevel ? `h${props.headingLevel}` : "h2") as "h2" | "h3";

    const attendanceWidth = `${eventsVariables().spacing.attendanceOffset + (props.longestCharCount || 0)}ex`;

    return (
        <li className={classNames(classes.item, props.className)}>
            <article className={classes.result}>
                <SmartLink
                    to={props.url}
                    className={classes.link}
                    tabIndex={0}
                    style={{ maxWidth: calc(`100% - ${attendanceWidth}`) }}
                >
                    <DateTime className={classes.dateCompact} type={DateFormats.COMPACT} {...props.date} />
                    <div className={classes.main}>
                        <HeadingTag className={classes.title}>{props.name}</HeadingTag>
                        {props.excerpt && (
                            <Paragraph className={classes.excerpt}>
                                <TruncatedText maxCharCount={160}>{props.excerpt}</TruncatedText>
                            </Paragraph>
                        )}
                        <div className={classes.metas}>
                            {props.location && <div className={classes.meta}>{props.location}</div>}
                            <div className={classes.meta}>
                                <DateTime type={DateFormats.DEFAULT} {...props.date} />
                            </div>
                        </div>
                    </div>
                </SmartLink>
                <div
                    className={classes.attendance}
                    style={{
                        flexBasis: `${attendanceWidth}`,
                        width: `${attendanceWidth}`,
                    }}
                >
                    <EventAttendanceDropDown attendance={props.attendance} options={props.attendanceOptions} />
                </div>
            </article>
        </li>
    );
}
