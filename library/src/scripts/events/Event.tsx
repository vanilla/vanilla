import React from "react";
import DateTime, { DateFormats, IDateTime } from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { eventsClasses } from "@library/events/eventStyles";
import Paragraph from "@library/layout/Paragraph";
import TruncatedText from "@library/content/TruncatedText";
import EventAttendanceDropDown, { EventAttendance } from "@library/events/EventAttendanceDropDown";

interface IEventDate extends Omit<IDateTime, "mode" | "type"> {}

export interface IEvent {
    date: IEventDate;
    name: string;
    excerpt?: string;
    location: string;
    url: string;
    headingLevel: 2 | 3;
    attendance: EventAttendance;
}

/**
 * Component for displaying an accessible nicely formatted time string.
 */
export function Event(props: IEvent) {
    const classes = eventsClasses();

    const HeadingTag = (props.headingLevel ? `h${props.headingLevel}` : "h2") as "h2" | "h3";

    return (
        <li className={classes.item}>
            <article className={classes.result}>
                <SmartLink to={props.url} className={classes.link} tabIndex={0}>
                    <DateTime type={DateFormats.COMPACT} {...props.date} />
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
                                <DateTime type={DateFormats.DEFAULT} />
                            </div>
                        </div>
                    </div>
                </SmartLink>
                <div className={classes.attendance}>
                    <EventAttendanceDropDown attendance={props.attendance} />
                </div>
            </article>
        </li>
    );
}
