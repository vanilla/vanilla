/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { unit } from "@library/styles/styleHelpers";
import DateTime from "@library/content/DateTime";

export default {
    title: "Event Story",
    parameters: {
        inPanel: false,
    },
};

export function StoryEvents(config) {
    const events = [
        {
            date: {
                timestamp: "2020-04-22T14:31:19Z",
            },
        },
    ];

    return (
        <>
            <StoryHeading depth={1}>Event List - Desktop</StoryHeading>
            <EventsList />
        </>
    );
}

export const NoDescription = storyWithConfig(
    {
        useWrappers: false,
    },
    () => (
        <div style={{ width: unit(300) }}>
            <StoryEvents title="Panel Style" inPanel={true} />
        </div>
    ),
);
