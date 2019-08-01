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

const reactionsStory = storiesOf("Components", module);

reactionsStory.add("Buttons", () => {
    return (
        <>
            <StoryHeading depth={1}>Buttons</StoryHeading>
            <StoryTiles>
                <>
                    <StoryTile>
                        <Button>Standard</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Text</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Text Primary</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Icon</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Icon_Compact</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Compact</Button>
                    </StoryTile>
                    <StoryTile>
                        <Button>Primary</Button>
                        <StoryParagraph>Uses primary color as BG</StoryParagraph>
                    </StoryTile>
                    <StoryTile>
                        <Button>Compact Primary</Button>
                        <StoryParagraph>Uses primary color as BG, with less padding</StoryParagraph>
                    </StoryTile>
                    <StoryTile>
                        <Button>Transparent</Button>
                        <StoryParagraph>No background color</StoryParagraph>
                    </StoryTile>
                    <StoryTile>
                        <Button>Translucid</Button>
                        <StoryParagraph>Fake transparency of the text with colors</StoryParagraph>
                    </StoryTile>
                    <StoryTile>
                        <Button>Inverted</Button>
                        <StoryParagraph>Inverted colors</StoryParagraph>
                    </StoryTile>
                    <StoryTile>
                        <Button>Cutstom</Button>
                        <StoryParagraph>No special styling, for special case buttons.</StoryParagraph>
                    </StoryTile>
                </>
            </StoryTiles>
        </>
    );
});

reactionsStory.add("Modals", () => {
    return <StoryHeading depth={1}>Modals</StoryHeading>;
});

reactionsStory.add("Examples", () => {
    return <StoryHeading depth={1}>Modal Examples</StoryHeading>;
});
