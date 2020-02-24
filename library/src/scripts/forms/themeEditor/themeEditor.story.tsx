/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import ThemeEditorInputBlock from "@library/forms/themeEditor/ThemeEditorInputBlock";
import ColorPicker from "@library/forms/themeEditor/ColorPicker";
import { percent } from "csx";
import { storiesOf } from "@storybook/react";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";

const story = storiesOf("Theme", module);

story.add("Theme Editor", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Theme Editor</StoryHeading>
            <div style={{ width: percent(100), maxWidth: "500px", margin: "auto" }}>
                <ColorPickerBlock
                    colorPicker={{ variableID: "global.something.or.other.color" }}
                    inputBlock={{ label: "testma" }}
                />
            </div>
        </StoryContent>
    );
});
