/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DateTime, { DateFormats, IDateTime } from "@library/content/DateTime";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTile } from "@library/storybook/StoryTile";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";

export default {
    title: "Date Formats",
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
    ] as IDateTime[];

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
