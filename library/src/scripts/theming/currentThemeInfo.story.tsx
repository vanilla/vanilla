/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import CurrentThemeInfo from "./CurrentThemeInfo";
import { StoryTile } from "@library/storybook/StoryTile";

const story = storiesOf("Theme", module);

story.add("Current Theme", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Current Theme</StoryHeading>
                <StoryTile>
                    <CurrentThemeInfo
                        name={"Keystone"}
                        authors={"Author1, Author2, Author3"}
                        description={
                            "A responsive Vanilla theme with customization options. A responsive Vanilla theme with customization options."
                        }
                    />
                </StoryTile>
            </StoryContent>
        </>
    );
});
