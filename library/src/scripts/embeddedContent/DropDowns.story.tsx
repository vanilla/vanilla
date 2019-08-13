/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryExampleDropDown } from "./StoryExampleDropDown";

const story = storiesOf("Components", module);

// Radio as tabs

const doNothing = () => {};

story.add("Dropdowns", () => {
    const doNothing = () => {
        return;
    };

    return (
        <StoryContent>
            <StoryHeading depth={1}>Drop Down</StoryHeading>
            <StoryParagraph>
                Note that these dropdowns can easily be transformed into modals on mobile by using the
                &quot;openAsModal&quot; property.
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact>
                    <StoryExampleDropDown />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
