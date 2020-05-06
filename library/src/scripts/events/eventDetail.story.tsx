/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { EventAttendance } from "@library/events/eventOptions";

export default {
    title: "Event Details",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

const dummyEventData = [
    {
        name: "Watercolor for beginners",
        excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        date: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Your home",
        url: "http://google.ca",
        attendance: EventAttendance.MAYBE,
    },
] as IEventExtended;

export function StoryEventDetails(props: { data: IEventExtended; title: string }) {
    const { data = dummyEventData, title = "Event Details" } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <EventDetails {...data} />
        </>
    );
}
