/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";

export default {
    title: "Date/Time Formats",
    parameters: {},
};

export function StoryDateTime(config) {
    const testDates = [
        {
            timestamp: "2020-04-22T14:31:19Z",
            type: DateFormats.DEFAULT,
        },
        {
            timestamp: "2020-04-22T14:31:19Z",
            type: DateFormats.EXTENDED,
        },
        {
            timestamp: "2020-04-22T14:31:19Z",
            type: DateFormats.COMPACT,
        },
    ];

    const content = testDates.map((date, i) => {
        return (
            <StoryTileAndTextCompact key={i}>
                <DateTime {...date} />
            </StoryTileAndTextCompact>
        );
    });

    return (
        <StoryContent>
            <StoryHeading depth={1}>Date Formats</StoryHeading>
            {content}
        </StoryContent>
    );
}
