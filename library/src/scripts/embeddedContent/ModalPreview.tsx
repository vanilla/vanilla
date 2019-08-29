/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryTiles } from "@library/storybook/StoryTiles";
import Button from "@library/forms/Button";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { buttonClasses, ButtonTypes, buttonUtilityClasses } from "@library/forms/buttonStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTile } from "@library/storybook/StoryTile";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";
import { CheckCompactIcon, CloseCompactIcon } from "@library/icons/common";
import { ComposeIcon } from "@library/icons/titleBar";
import Paragraph from "@library/layout/Paragraph";

const story = storiesOf("Components", module);

story.add("Modals", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Modals</StoryHeading>
            <StoryParagraph>
                Click button to see modals. Note that they are rendered through a{" "}
                <a href="https://reactjs.org/docs/portals.html" rel="noopener noreferrer" target="_blank">
                    react portal.
                </a>
            </StoryParagraph>

            <StoryTiles>
                <StoryTileAndTextCompact>
                    <Button>Standard Modal</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button>Standard Modal</Button>
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});

story.add("Examples", () => {
    return <StoryHeading depth={1}>Modal Examples</StoryHeading>;
});
