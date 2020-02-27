/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { percent } from "csx";
import { storiesOf } from "@storybook/react";
import ThemeBuilderForm from "@themingapi/theme/ThemeBuilderForm";

const story = storiesOf("Theme", module);

story.add("Theme Builder", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Theme Editor</StoryHeading>
            <aside
                style={{
                    width: percent(100),
                    maxWidth: "376px",
                    margin: "auto",
                    backgroundColor: "#f5f6f7",
                    padding: "16px",
                }}
            >
                <ThemeBuilderForm />
            </aside>
        </StoryContent>
    );
});
