/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryExampleDropDown } from "./StoryExampleDropDown";
import { FlyoutType } from "@library/flyouts/DropDown";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import StoryExampleMessagesDropDown from "@library/embeddedContent/StoryExampleDropDownMessages";
import StorybookExampleNotificationsDropDown from "@library/headers/mebox/pieces/StorybookExampleNotificationsDropDown";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

const story = storiesOf("Components", module);

story.add(
    "Dropdowns",
    () => {
        const doNothing = () => {
            return;
        };

        const toolBarColors = titleBarVariables().colors;

        return (
            <StoryContent>
                <StoryHeading depth={1}>Drop Down</StoryHeading>
                <StoryParagraph>
                    Note that these dropdowns are automatically transformed into modals on mobile, and will
                    automatically determine the direction they need to open into.
                </StoryParagraph>
                <StoryParagraph>They can be forced into being a modal with the `openAsModal` prop.</StoryParagraph>
                <StoryParagraph>
                    They can be forced into a particular open direction with the `openDirection` prop.
                </StoryParagraph>
                <StoryTiles>
                    <StoryTileAndTextCompact>
                        <StoryExampleDropDown
                            defaultsOpen={true}
                            flyoutType={FlyoutType.LIST}
                            title={"As List"}
                            text={"Expects all chidren to be `<li/>`"}
                        />
                    </StoryTileAndTextCompact>
                    <StoryTileAndTextCompact backgroundColor={toolBarColors.bg}>
                        <StoryExampleMessagesDropDown />
                    </StoryTileAndTextCompact>
                    <StoryTileAndTextCompact backgroundColor={toolBarColors.bg}>
                        <StorybookExampleNotificationsDropDown />
                    </StoryTileAndTextCompact>
                </StoryTiles>
            </StoryContent>
        );
    },
    {
        chromatic: {
            viewports: [layoutVariables().panelLayoutBreakPoints.noBleed, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
);
