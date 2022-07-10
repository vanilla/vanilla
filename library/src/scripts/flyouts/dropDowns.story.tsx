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
import { StoryExampleDropDown } from "@library/flyouts/StoryExampleDropDown";
import { FlyoutType } from "@library/flyouts/DropDown";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import StoryExampleMessagesDropDown from "@library/flyouts/StoryExampleDropDownMessages";
import StorybookExampleNotificationsDropDown from "@library/headers/mebox/pieces/StorybookExampleNotificationsDropDown";
import { oneColumnVariables } from "@library/layout/Section.variables";

const story = storiesOf("Components/Dropdowns", module);

story.add(
    "All",
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
            viewports: [oneColumnVariables().breakPoints.noBleed, oneColumnVariables().breakPoints.xs],
        },
    },
);
