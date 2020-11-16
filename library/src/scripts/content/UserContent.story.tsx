/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import UserContent from "@library/content/UserContent";
import React from "react";
import { STORY_CONTENT_RICH, STORY_CONTENT_LEGACY, STORY_CONTENT_TABLES } from "@library/content/UserContent.storyData";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { legacyCssDecorator } from "@dashboard/__tests__/legacyCssDecorator";
import { TableStyle } from "@library/content/userContentStyles";

export default {
    title: "User Content/Content",
};

export function Rich() {
    return <UserContent content={STORY_CONTENT_RICH} />;
}

export const Legacy = storyWithConfig({}, () => {
    return <UserContent content={STORY_CONTENT_LEGACY} />;
});

Legacy.decorators = [legacyCssDecorator];

function makeTableStory(tableStyle: TableStyle) {
    const storyFn = storyWithConfig(
        {
            themeVars: {
                userContent: {
                    tables: {
                        style: tableStyle,
                    },
                },
            },
        },
        () => {
            return <UserContent content={STORY_CONTENT_TABLES} />;
        },
    );
    storyFn.parameters = {
        chromatic: {
            viewports: [1200, 500],
        },
    };
    return storyFn;
}

export const TableHorizontal = makeTableStory(TableStyle.HORIZONTAL_BORDER);
export const TableHorizontalStriped = makeTableStory(TableStyle.HORIZONTAL_BORDER_STRIPED);
export const TableVertical = makeTableStory(TableStyle.VERTICAL_BORDER);
export const TableVerticalStriped = makeTableStory(TableStyle.VERTICAL_BORDER_STRIPED);
