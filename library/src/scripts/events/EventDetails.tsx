/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { IEvent } from "@library/events/Event";
import { DataList } from "@library/dataLists/dataList";

export interface IEventExtended {
    event: IEvent;
}

/**
 * Component for displaying an event details
 */
export function EventDetails(props: IEvent) {
    const classes = eventsClasses();
    return (
        <div className={classes.details}>
            <DataList data={[]} />
        </div>
    );
}
