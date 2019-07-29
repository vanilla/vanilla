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

const reactionsStory = storiesOf("Quick Start", module);

reactionsStory.add("Colors", () => {
    // @ts-ignore
    return (
        <>
            <StoryHeading depth={1}>Colors</StoryHeading>

            <StoryHeading>More resources</StoryHeading>
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
        </>
    );
});
