/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import ThemePreviewCard from "./ThemePreviewCard";
import { unit } from "@library/styles/styleHelpers";

const story = storiesOf("Theme", module);

story.add("Preview Card", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Preview Card</StoryHeading>
                <div style={{ paddingBottom: unit(52), textAlign: "center" }}>
                    <ThemePreviewCard
                        globalBg={"#fff"}
                        globalPrimary={"#985E6D"}
                        globalFg={"#555a62"}
                        titleBarBg={"#0291db"}
                        titleBarFg={"#fff"}
                        isActiveTheme={false}
                        isThemeDb={false}
                    />
                </div>
                <StoryHeading depth={1}>Preview Card with dropdown</StoryHeading>
                <div style={{ paddingBottom: unit(52), textAlign: "center" }}>
                    <ThemePreviewCard
                        globalBg={"#fff"}
                        globalPrimary={"#985E6D"}
                        globalFg={"#555a62"}
                        titleBarBg={"#0291db"}
                        titleBarFg={"#fff"}
                        isActiveTheme={false}
                        isThemeDb={true}
                    />
                </div>
                <StoryHeading depth={1}>Preview card (with no hover)</StoryHeading>
                <ThemePreviewCard
                    globalBg={"#fff"}
                    globalPrimary={"#985E6D"}
                    globalFg={"#555a62"}
                    titleBarBg={"#0291db"}
                    titleBarFg={"#fff"}
                    isActiveTheme={true}
                />
            </StoryContent>
        </>
    );
});
