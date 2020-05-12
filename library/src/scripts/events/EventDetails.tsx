/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { IEvent } from "@library/events/Event";
import { IUserFragment } from "@library/@types/api/users";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { t } from "@vanilla/i18n/src";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { EventAttendance } from "@library/events/eventOptions";
import UserContent from "@library/content/UserContent";
import { EventAttendees } from "@library/events/Attendees";
import { FromToDateTime } from "@library/content/FromToDateTime";
import { dummyEventDetailsData } from "@library/dataLists/dummyEventData";
import { DataList } from "@library/dataLists/DataList";

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

    const eventMetaData = [
        {
            key: t("When"),
            value: <FromToDateTime dateStart={props.dateStart} dateEnd={props.dateEnd} />,
        },
        {
            key: t("Where"),
            value: props.location,
        },
        {
            key: t("Organizer"),
            value: props.organizer,
        },
    ];

    return (
        <div className={classes.details}>
            <DataList data={eventMetaData} className={classes.section} caption={t("Event Details")} />
            <ButtonTabs
                activeTab={props.attendance}
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
                data={props.going!}
                title={t("Going")}
                emptyMessage={t("Nobody has confirmed their attendance yet.")}
                extra={props.going?.length}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody is on the fence right now.")}
                data={props.maybe!}
                title={t("Maybe")}
                extra={props.maybe?.length}
                separator={true}
            />
            <EventAttendees
                emptyMessage={t("Nobody has declined the invitation so far.")}
                data={props.notGoing!}
                title={t("Not going")}
                extra={props.notGoing?.length}
                separator={true}
            />
        </div>
    );
}
