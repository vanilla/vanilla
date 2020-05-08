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
import Heading from "@library/layout/Heading";

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

    const eventMetaData = [
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
            <DataList data={eventMetaData} className={classes.section} />
            <ButtonTabs accessibleTitle={t("Are you going?")} setData={({}) => {}}>
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} />
            </ButtonTabs>

            {props.about && (
                <div className={classes.section}>
                    <hr className={classes.separator} />
                    <Heading depth={2} className={classes.sectionTitle} renderAsDepth={"custom"}>
                        {t("About the event")}
                    </Heading>
                    <UserContent className={classes.description} content={props.about} />
                </div>
            )}

            <EventAttendees
                data={dummyEventDetailsData.going!}
                title={t("Going")}
                emptyMessage={t("Nobody has confirmed their attendance yet.")}
                extra={552}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody is on the fence right now.")}
                data={dummyEventDetailsData.maybe!}
                title={t("Maybe")}
                extra={1201}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody has declined the invitation so far.")}
                data={dummyEventDetailsData.notGoing!}
                title={t("Not going")}
                separator={true}
            />
        </div>
    );
}
