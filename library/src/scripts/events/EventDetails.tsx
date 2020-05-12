/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { IEvent } from "@library/events/Event";
import { DataList } from "@library/dataLists/dataList";
import { IUserFragment } from "@library/@types/api/users";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { t } from "@vanilla/i18n/src";
import { dummyEventDetailsData } from "@library/dataLists/dummyEventData";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { EventAttendance } from "@library/events/eventOptions";
import UserContent from "@library/content/UserContent";
import { EventAttendees } from "@library/events/Attendees";
import { FromToDateTime } from "@library/content/FromToDateTime";

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
                <FromToDateTime dateStart={dummyEventDetailsData.dateStart} dateEnd={dummyEventDetailsData.dateEnd} />
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
    ];

    return (
        <div className={classes.details}>
            <DataList data={eventMetaData} className={classes.section} caption={t("Event Details")} />
            <ButtonTabs
                activeTab={EventAttendance.GOING}
                accessibleTitle={t("Are you going?")}
                setData={(data: EventAttendance) => {}}
                className={classes.attendanceSelector}
            >
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} className={"isLast"} />
            </ButtonTabs>

            {props.about && (
                <div className={classes.section}>
                    <hr className={classes.separator} />
                    <h2 className={classes.sectionTitle}>{t("About the event")}</h2>
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
