/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTiles } from "@library/storybook/StoryTiles";
import ThemePreviewCard from "./ThemePreviewCard";

const story = storiesOf("Theme", module);

story.add("Preview Card", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Preview Card</StoryHeading>

                <ThemePreviewCard />
            </StoryContent>
        </>
    );
});
