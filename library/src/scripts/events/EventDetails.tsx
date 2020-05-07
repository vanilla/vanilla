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

    let dataList = [
        {
            key: t("When"),
            value: `${(<DateTime {...props.dateStart} type={DateFormats.EXTENDED} />)}`,
        },
    ];
    if (props.dateEnd) {
        dataList.push({
            key: t("To"),
            value: `${(<DateTime {...props.dateEnd} type={DateFormats.EXTENDED} />)}`,
        });
    }
    if (props.location) {
        dataList.push({
            key: t("Where"),
            value: props.location,
        });
    }
    if (props.organizer) {
        dataList.push({
            key: t("Where"),
            value: props.location,
        });
    }

    return (
        <div className={classes.details}>
            <DataList data={dataList} />
        </div>
    );
}
