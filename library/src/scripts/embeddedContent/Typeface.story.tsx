/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";

const reactionsStory = storiesOf("Typeface", module);

reactionsStory.add("Typeface", () => {
    return (
        <>
            <StoryHeading depth={1}>Typeface</StoryHeading>
        </>
    );
});
