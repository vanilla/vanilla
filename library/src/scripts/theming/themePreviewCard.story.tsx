/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import ThemePreviewCard from "./ThemePreviewCard";

const story = storiesOf("Theme", module);

const margins = {
    marginRight: 24,
    marginBottom: 24,
};

story.add("Preview Card", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Preview Card in different sizes</StoryHeading>
                <div style={{ width: 200, height: 150, ...margins }}>
                    <ThemePreviewCard isActiveTheme={false} canCopy={true} />
                </div>
                <div style={{ width: 310, height: 220, ...margins }}>
                    <ThemePreviewCard isActiveTheme={false} canCopy={true} />
                </div>
                <div style={{ width: 400, height: 300, ...margins }}>
                    <ThemePreviewCard isActiveTheme={false} canCopy={true} />
                </div>
                <StoryHeading depth={1}>Preview Card with dropdown</StoryHeading>
                <ThemePreviewCard isActiveTheme={false} canEdit={true} canDelete={true} />
                <StoryHeading depth={1}>Preview Card with dropdown</StoryHeading>
                <ThemePreviewCard isActiveTheme={false} canEdit={true} canDelete={true} />
                <StoryHeading depth={1}>Preview card (with no hover)</StoryHeading>
                <ThemePreviewCard noActions isActiveTheme={true} />

                <StoryHeading depth={1}>Preview card (Colored)</StoryHeading>

                <ThemePreviewCard globalPrimary={"#985E6D"} noActions isActiveTheme={true} />

                <StoryHeading depth={1}>Preview card (Dark Mode)</StoryHeading>
                <ThemePreviewCard
                    globalBg={"#0e0f19"}
                    globalPrimary={"#3ebdff"}
                    globalFg={"#fff"}
                    titleBarBg={"#0e0f19"}
                    titleBarFg={"#fff"}
                    noActions
                    isActiveTheme={true}
                />
            </StoryContent>
        </>
    );
});
