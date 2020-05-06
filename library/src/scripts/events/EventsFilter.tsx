/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IEvent } from "@library/events/Event";
import { t } from "@vanilla/i18n/src";
import { eventsClasses } from "@library/events/eventStyles";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

export interface IProps {
    events: IEvent;
    viewMoreLink: string;
    viewMoreText?: string;
}

export enum EventFilterTypes {
    ALL = "all",
    UPCOMING = "upcoming events",
    PAST = "past events",
}

/**
 * Component for displaying an event filters
 */
export default function EventFilter(props: { filter: EventFilterTypes }) {
    const { filter = EventFilterTypes.ALL } = props;

    const options = [
        { name: t("All"), value: EventFilterTypes.ALL },
        { name: t("Upcoming Events"), value: EventFilterTypes.UPCOMING },
        { name: t("Past Events"), value: EventFilterTypes.PAST },
    ] as ISelectBoxItem[];

    const activeOption = options.find(option => option.value === filter);
    const id = uniqueIDFromPrefix("attendanceFilter");
    const classes = eventsClasses();
    return (
        <div className={classes.filter}>
            <span id={id} className={classes.filterLabel}>
                {t("View:")}
            </span>
            <SelectBox
                className={eventsClasses().dropDown}
                widthOfParent={false}
                options={options}
                describedBy={id}
                value={activeOption}
                renderLeft={true}
                offsetPadding={true}
            />
        </div>
    );
}
