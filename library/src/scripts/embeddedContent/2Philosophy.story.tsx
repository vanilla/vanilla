/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

const reactionsStory = storiesOf("Philosophy", module);

reactionsStory.add("Goals", () => {
    return (
        <>
            <StoryHeading depth={1}>Goals</StoryHeading>
            <StoryParagraph>
                As our clients grow, it becomes increasingly difficult to update our product. With so many
                customizations, it is very difficult to know the impact of updates. In the future, we want less
                customization, but we want to allow options to our clients. We would like requests from clients to
                become options or new features that will be available to everyone. This requires us to cut down as much
                as possible on one off changes.
            </StoryParagraph>
            <StoryParagraph>
                With components in Storybook, we can run automated tests for regressions. We will be able to update more
                easily. Cut down on support.
            </StoryParagraph>
        </>
    );
});

reactionsStory.add("Variables", () => {
    return (
        <>
            <StoryHeading depth={1}>Variables</StoryHeading>
            <StoryParagraph>
                The themes are built with many variables. Having said that, with only a few variables set, you will have
                a functional theme.
            </StoryParagraph>
            <StoryParagraph>The most important being:</StoryParagraph>
            <ul>
                <li />
            </ul>
        </>
    );
});

reactionsStory.add("Colors", () => {
    return (
        <>
            <StoryHeading depth={1}>Colors</StoryHeading>
        </>
    );
});
