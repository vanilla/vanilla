/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { t } from "@library/utility/appUtils";
import NextPrevious from "@library/navigation/NextPrevious";
import Heading from "@library/layout/Heading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { typographyClasses } from "@library/styles/typographyStyles";

const story = storiesOf("UI Content", module);

story.add("Headings", () => {
    const classesTypography = typographyClasses();
    return (
        <StoryContent>
            <StoryHeading depth={1}>Headings</StoryHeading>
            <StoryParagraph>Headings can get the style of any depth, but semantically be different</StoryParagraph>
            <Heading depth={1} renderAsDepth={1} title={"True H1"} />
            <StoryParagraph>Page Title style</StoryParagraph>
            <Heading depth={3} renderAsDepth={1} title={"H3 with the style of an H1"} />
            <StoryParagraph>
                The styles are independent from the markup, so you can use any heading that is most semantic.
            </StoryParagraph>
            <Heading depth={2} title={"Sub Title"} className={classesTypography.subTitle} />
            <StoryParagraph>Mostly sub headings in panels</StoryParagraph>
            <Heading depth={2} title={"Component sub title"} className={classesTypography.componentSubTitle} />
            <StoryParagraph>Sub headings in components</StoryParagraph>
        </StoryContent>
    );
});
