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
import { closeCompact } from "@library/icons/common";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

const reactionsStory = storiesOf("Components", module);

/*
    STANDARD = "standard",
    PRIMARY = "primary",
    TRANSPARENT = "transparent",
    COMPACT = "compact",
    COMPACT_PRIMARY = "compactPrimary",
    TRANSLUCID = "translucid",
    INVERTED = "inverted",
    CUSTOM = "custom",
    TEXT = "text",
    TEXT_PRIMARY = "textPrimary",
    ICON = "icon",
    ICON_COMPACT = "iconCompact",
 */

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

                <StoryTileAndTextCompact mouseOverText={"Text Button"}>
                    <Button baseClass={ButtonTypes.TEXT}>Text</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button>Primary Button</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button>Icon</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact>
                    <Button>Icon_Compact</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    title={"Compact"}
                    text={
                        "Note that there are 'compact' versions of some icons to have less padding in them. This style is mainly for buttons with just an icon"
                    }
                >
                    <Button baseClass={ButtonTypes.ICON_COMPACT}>{closeCompact()}</Button>
                </StoryTileAndTextCompact>

                <StoryTileAndTextCompact
                    text={"Note that there are 'compact' versions of some icons to have less padding in them"}
                >
                    <Button>Compact Primary</Button>
                </StoryTileAndTextCompact>

                <StoryTileAndTextCompact text={"Fake transparency of the text with colors"}>
                    <Button>Translucid</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Inverted bg and fg"}>
                    <Button>Inverted</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"No special styling, for special case buttons"}>
                    <Button>Custom</Button>
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
