/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import ThemePreviewCardComponent from "./ThemePreviewCard";

export default {
    title: "Theme UI",
};

const margins = {
    marginRight: 24,
    marginBottom: 24,
};

export function ThemePreviewCard() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Preview Card in different sizes</StoryHeading>
            <div style={{ width: 200, height: 150, ...margins }}>
                <ThemePreviewCardComponent canCopy={true} />
            </div>
            <div style={{ width: 310, height: 220, ...margins }}>
                <ThemePreviewCardComponent canCopy={true} />
            </div>
            <div style={{ width: 400, height: 300, ...margins }}>
                <ThemePreviewCardComponent canCopy={true} />
            </div>
            <StoryHeading depth={1}>Preview Card with dropdown</StoryHeading>
            <ThemePreviewCardComponent canEdit={true} canDelete={true} />
            <StoryHeading depth={1}>Preview Card with dropdown</StoryHeading>
            <ThemePreviewCardComponent canEdit={true} canDelete={true} />
            <StoryHeading depth={1}>Preview card (with no hover)</StoryHeading>
            <ThemePreviewCardComponent noActions />

            <StoryHeading depth={1}>Preview card (Colored)</StoryHeading>

            <ThemePreviewCardComponent preview={{ variables: { globalPrimary: "#985E6D" } }} noActions />

            <StoryHeading depth={1}>Preview card (Dark Mode)</StoryHeading>
            <ThemePreviewCardComponent
                preview={{
                    variables: {
                        globalBg: "#0e0f19",
                        globalPrimary: "#3ebdff",
                        globalFg: "#fff",
                        titleBarBg: "#0e0f19",
                        titleBarFg: "#fff",
                    },
                }}
                noActions
            />
        </StoryContent>
    );
}
