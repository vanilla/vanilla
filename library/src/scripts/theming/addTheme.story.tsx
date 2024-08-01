/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { AddTheme } from "./AddTheme";
import { PlusIcon } from "@library/icons/common";

export default {
    title: "Theme UI",
};

export const _AddTheme = () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Add Theme</StoryHeading>
                <AddTheme onAdd={<PlusIcon />} />
            </StoryContent>
        </>
    );
};
