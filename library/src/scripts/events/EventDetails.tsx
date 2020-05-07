/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { IEvent } from "@library/events/Event";
import { DataList, IData } from "@library/dataLists/dataList";
import { IUserFragment } from "@library/@types/api/users";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { t } from "@vanilla/i18n/src";
import { dummyEventDetailsData } from "@library/dataLists/dummyEventData";
import { renderToString } from "react-dom/server";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import { ButtonTab } from "@library/forms/buttonTabs/ButtonTab";
import { EventAttendance } from "@library/events/eventOptions";
import UserContent from "@library/content/UserContent";
import { EventAttendees } from "@library/events/Attendees";

export interface IEventExtended extends IEvent {
    organizer: string;
    about?: string;
    going?: IUserFragment[];
    maybe?: IUserFragment[];
    notGoing?: IUserFragment[];
}

/**
 * Component for displaying an event details
 */
export function EventDetails(props: IEventExtended) {
    const classes = eventsClasses();

    const startDate = <DateTime {...dummyEventDetailsData.dateStart} type={DateFormats.EXTENDED} />;
    const endDate = <DateTime {...dummyEventDetailsData.dateEnd} type={DateFormats.EXTENDED} />;

    const dummyData = [
        {
            key: t("When"),
            value: (
                <span
                    dangerouslySetInnerHTML={{
                        __html: `${renderToString(startDate)}${
                            dummyEventDetailsData.dateEnd ? ` - ${renderToString(endDate)}` : ""
                        }`,
                    }}
                />
            ),
        },
        {
            key: t("Where"),
            value: dummyEventDetailsData.location,
        },
        {
            key: t("Organizer"),
            value: dummyEventDetailsData.organizer,
        },
    ] as IData[];

    return (
        <div className={classes.details}>
            <DataList data={dummyData} />
            <ButtonTabs accessibleTitle={t("Are you going?")} setData={({}) => {}}>
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} />
            </ButtonTabs>

            {props.excerpt && (
                <>
                    <hr className={classes.separator} />
                    <UserContent content={props.excerpt} />
                </>
            )}

            <EventAttendees data={dummyEventDetailsData.going!} title={t("Going")} extra={552} separator={true} />
            <EventAttendees data={dummyEventDetailsData.maybe!} title={t("Maybe")} extra={1201} separator={true} />
            <EventAttendees data={dummyEventDetailsData.notGoing!} title={t("No going")} separator={true} />
        </div>
    );
}
