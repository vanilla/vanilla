/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";

import { IEvent } from "@library/events/Event";
import { eventsClasses } from "@library/events/eventStyles";

export interface IEventList {
    data: IEvent[];
}

/**
 * Component for displaying an accessible nicely formatted time string.
 */
export function EventsList(props: IEventList) {
    if (!props.data || props.data.length === 0) {
        return null;
    }
    const classes = eventsClasses();
    return (
        <ul className={classes.list}>
            {props.data.map((event, i) => {
                return <Event {...event} key={i} />;
            })}
        </ul>
    );
}
