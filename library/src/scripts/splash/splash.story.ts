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
import Splash from "@library/splash/Splash";

const story = storiesOf("Home Page", module);

story.add("Splash", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}></StoryHeading>
            <Splash action={splashAction} outerBackgroundImage={bannerImage} title={knowledgeBase.name} />
        </StoryContent>
    );
});
