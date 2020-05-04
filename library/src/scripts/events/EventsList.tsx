/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";

import { IEvent } from "@library/events/Event";
import { eventsClasses } from "@library/events/eventStyles";
import { Event } from "@library/events/Event";
import classNames from "classnames";

export interface IEventList {
    headingLevel?: 2 | 3;
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
                return (
                    <Event
                        className={classNames({ isFirst: i === 0 })}
                        headingLevel={props.headingLevel}
                        {...event}
                        key={i}
                    />
                );
            })}
        </ul>
    );
}
