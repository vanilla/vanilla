/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryTiles } from "@library/storybook/StoryTiles";
import Button from "@library/forms/Button";
import { StoryTile } from "@library/storybook/StoryTile";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTileAndText } from "@library/storybook/StoryTileAndText";
import { StoryContent } from "@library/storybook/StoryContent";

const reactionsStory = storiesOf("Components", module);

reactionsStory.add("Buttons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Buttons</StoryHeading>
            <StoryTiles>
                <StoryTileAndText compact={true} mouseOverText={"Standard Button"}>
                    <Button>Standard</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} mouseOverText={"Text Button"}>
                    <Button>Text</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"Uses the primary color as the background"}>
                    <Button>Primary Button</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"blah"}>
                    <Button>Icon</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"blah"}>
                    <Button>Icon_Compact</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"blah"}>
                    <Button>Compact</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"Uses primary color as BG"}>
                    <Button>Primary</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"bUses primary color as BG, with less paddinglah"}>
                    <Button>Compact Primary</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"No background color"}>
                    <Button>Transparent</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"Fake transparency of the text with colors"}>
                    <Button>Translucid</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"Inverted bg and fg"}>
                    <Button>Inverted</Button>
                </StoryTileAndText>
                <StoryTileAndText compact={true} text={"No special styling, for special case buttons"}>
                    <Button>Custom</Button>
                </StoryTileAndText>
            </StoryTiles>
        </StoryContent>
    );
});

reactionsStory.add("Modals", () => {
    return <StoryHeading depth={1}>Modals</StoryHeading>;
});

reactionsStory.add("Examples", () => {
    return <StoryHeading depth={1}>Modal Examples</StoryHeading>;
});
