/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    TestMenuBarFlat,
    TestMenuBarNested,
    TestMenuBarNestedWithInlineAndInputSubItems,
} from "@library/MenuBar/MenuBar.fixtures";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";

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
            <StoryHeading>With inline and input type submenu items</StoryHeading>
            <TestMenuBarNestedWithInlineAndInputSubItems autoOpen />
        </StoryContent>
    );
}
