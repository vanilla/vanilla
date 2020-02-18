/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { AddTheme } from "./AddTheme";
import { PlusIcon } from "@library/icons/common";
import { manageThemingClasses } from "@themingapi/theming-ui-settings/manageThemingStyles";

const story = storiesOf("Theme", module);
const classes = manageThemingClasses();
story.add("Add Theme", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Add Theme</StoryHeading>
                <AddTheme className={classes.gridItem} onAdd={<PlusIcon />} />
            </StoryContent>
        </>
    );
});
