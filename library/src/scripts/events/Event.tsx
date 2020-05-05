import React from "react";
import DateTime, { DateFormats, IDateTime } from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { eventsClasses, eventsVariables } from "@library/events/eventStyles";
import Paragraph from "@library/layout/Paragraph";
import TruncatedText from "@library/content/TruncatedText";
import EventAttendanceDropDown, { EventAttendance } from "@library/events/EventAttendanceDropDown";
import classNames from "classnames";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { calc, percent } from "csx";
import { AttendanceStamp } from "@library/events/AttendanceStamp";
import { globalVariables } from "@library/styles/globalStyleVars";

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
    compact?: boolean;
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
    const showAttendance = props.compact && props.attendance !== EventAttendance.NOT_GOING;
    return (
        <li className={classNames(classes.item, props.className)}>
            <article className={classes.result}>
                <SmartLink
                    to={props.url}
                    className={classes.link}
                    tabIndex={0}
                    style={
                        showAttendance
                            ? {
                                  maxWidth: calc(`100% - ${attendanceWidth}`),
                                  fontSize: props.compact
                                      ? eventsVariables().attendanceStamp.font.size
                                      : globalVariables().fonts.size.medium, // Needed for correct ex calculation
                              }
                            : {}
                    }
                >
                    <div className={classes.linkAlignment}>
                        <DateTime className={classes.dateCompact} type={DateFormats.COMPACT} {...props.date} />
                        <div className={classes.main}>
                            <HeadingTag
                                title={props.name}
                                className={classNames(classes.title, { isSingleLine: props.compact })}
                            >
                                {props.name}
                            </HeadingTag>
                            {props.excerpt && !props.compact && (
                                <Paragraph className={classes.excerpt}>
                                    <TruncatedText maxCharCount={160}>{props.excerpt}</TruncatedText>
                                </Paragraph>
                            )}
                            {(props.location || !props.compact) && (
                                <div className={classes.metas}>
                                    {props.location && <div className={classes.meta}>{props.location}</div>}
                                    {!props.compact && (
                                        <div className={classes.meta}>
                                            <DateTime type={DateFormats.DEFAULT} {...props.date} />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </SmartLink>
                {(showAttendance || !props.compact) && (
                    <div
                        className={classes.attendance}
                        style={{
                            flexBasis: `${attendanceWidth}`,
                            width: `${attendanceWidth}`,
                        }}
                    >
                        {showAttendance && (
                            <div className={classes.attendanceAlignment}>
                                <AttendanceStamp attendance={props.attendance} />
                            </div>
                        )}
                        {!props.compact && (
                            <EventAttendanceDropDown attendance={props.attendance} options={props.attendanceOptions} />
                        )}
                    </div>
                )}
            </article>
        </li>
    );
}
