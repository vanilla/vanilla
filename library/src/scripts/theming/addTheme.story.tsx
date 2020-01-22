/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import AddTheme from "./AddTheme";

const story = storiesOf("Theme", module);

story.add("Add Theme", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Add Theme</StoryHeading>
                <AddTheme />
            </StoryContent>
        </>
    );
});
