/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TestMenuBarFlat, TestMenuBarNested } from "@library/MenuBar/MenuBar.fixtures";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";

export default {
    title: "Components/MenuBar",
};

export function StaticMenuBar() {
    return (
        <StoryContent>
            <StoryHeading>No Subnavigation</StoryHeading>
            <TestMenuBarFlat />
            <StoryHeading>With subnavigation</StoryHeading>
            <TestMenuBarNested autoOpen />
        </StoryContent>
    );
}
