/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import UserContent from "@library/content/UserContent";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { TranslationGrid } from "@library/content/translationGrid/TranslationGrid";
import { translationGridData } from "@library/content/translationGrid/translationGrid.storyData";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

const story = storiesOf("Components", module);
story.add("Translation Grid", () => {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Translation Grid</StoryHeading>
                <StoryParagraph>Border is just to show it works in a scroll container, like a Modal</StoryParagraph>
            </StoryContent>
            <div
                style={{
                    height: "600px",
                    overflow: "auto",
                    border: "solid #1EA7FD 4px",
                    width: "1028px",
                    maxWidth: "100%",
                    margin: "auto",
                    position: "relative", // Scrolling container must have "position"
                }}
            >
                <TranslationGrid data={translationGridData.data} inScrollingContainer={true} />
            </div>
        </>
    );
});
