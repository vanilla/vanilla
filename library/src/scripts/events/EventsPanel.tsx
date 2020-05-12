import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n/src";
import { eventsClasses } from "@library/events/eventStyles";
import Heading from "@library/layout/Heading";
import { EventList, IEventList } from "@library/events/EventList";

export interface IProps {
    events: Omit<IEventList, "compact" | "headingLevel">;
    viewMoreLink: string;
    viewMoreText?: string;
    title?: string;
    headingLevel?: 2 | 3;
}

/**
 * Component for displaying an event in a panel
 */
export function EventsPanel(props: IProps) {
    const {
        events,
        viewMoreLink,
        viewMoreText = t("More Events"),
        title = t("Upcoming Events"),
        headingLevel = 2,
    } = props;
    if (events.data.length === 0) {
        return null;
    }
    const classes = eventsClasses();
    return (
        <>
            <Heading>{title}</Heading>
            <EventList {...events} compact={false} headingLevel={(headingLevel + 1) as 3 | 4} />
            <SmartLink to={viewMoreLink} className={classes.viewMore}>
                {viewMoreText}
            </SmartLink>
        </>
    );
}
