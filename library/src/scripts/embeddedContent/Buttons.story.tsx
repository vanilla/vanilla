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
import { ButtonTypes } from "@library/forms/buttonStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { checkCompact } from "@library/icons/common";

const reactionsStory = storiesOf("Components", module);

reactionsStory.add("Buttons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Buttons</StoryHeading>
            <StoryParagraph>
                Buttons use a{" "}
                <strong>
                    <code>baseClass</code>
                </strong>{" "}
                to specify the type of button you want. The types are available through the enum{" "}
                <strong>
                    <code>ButtonTypes</code>
                </strong>{" "}
                and if you want to do something custom and not overwrite the base button styles, use
                <strong>
                    {" "}
                    <code>ButtonTypes.CUSTOM</code>
                </strong>
                .
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact mouseOverText={"Standard Button"}>
                    <Button>Standard</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button baseClass={ButtonTypes.PRIMARY}>Primary</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact type="primary">
                    <Button baseClass={ButtonTypes.TRANSPARENT}>Transparent</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Fake transparency of the text with colors"}>
                    <Button baseClass={ButtonTypes.TRANSLUCID}>Translucid</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button baseClass={ButtonTypes.INVERTED}>Inverted</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button baseClass={ButtonTypes.TEXT}>Text</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button baseClass={ButtonTypes.TEXT_PRIMARY}>Text Primary</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button baseClass={ButtonTypes.ICON}>Icon</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={"Icon Compact"}>
                    <Button baseClass={ButtonTypes.ICON_COMPACT}>{checkCompact()}</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={
                        "If you don't want to fight against existing styles and write your own custom button, use the custom class."
                    }
                >
                    <Button baseClass={ButtonTypes.CUSTOM}>Custom</Button>
                </StoryTileAndTextCompact>
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
