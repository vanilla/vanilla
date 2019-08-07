/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import Paragraph from "@library/layout/Paragraph";
import { StoryUnorderedList } from "@library/storybook/StoryUnorderedList";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryLink } from "@library/storybook/StoryLink";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

const story = storiesOf("Quick Start", module);

story.add("Colors", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Colors</StoryHeading>
            <StoryParagraph>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut labor
                aliquip consequat adipiscing elit, sed do eiusmod tempor.
            </StoryParagraph>
            <StoryParagraph>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut labor
                aliquip consequat adipiscing elit, sed do eiusmod tempor.
            </StoryParagraph>
            <StoryHeading>More resources</StoryHeading>
            <StoryParagraph>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut labor
                aliquip consequat adipiscing elit, sed do eiusmod tempor.
            </StoryParagraph>
            <StoryUnorderedList>
                <>
                    <StoryListItem>
                        <StoryLink href="https://staff.vanillaforums.com/kb/articles/100-styling">Styling</StoryLink>
                    </StoryListItem>
                    <StoryListItem>
                        <StoryLink href="https://staff.vanillaforums.com/kb/articles/101-theming">Theming</StoryLink>
                    </StoryListItem>
                </>
            </StoryUnorderedList>
        </StoryContent>
    );
});
