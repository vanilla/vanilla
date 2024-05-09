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

const story = storiesOf("Navigation", module);

story.add("Next/Previous", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Next/Previous</StoryHeading>
            <StoryHeading>Both next and previous</StoryHeading>
            <NextPrevious
                accessibleTitle={t("More Articles")}
                prevItem={{
                    name: "The last article",
                    url: "#",
                }}
                nextItem={{
                    name: "The next article",
                    url: "#",
                }}
            />
            <StoryHeading>Only previous</StoryHeading>
            <NextPrevious
                accessibleTitle={t("More Articles")}
                prevItem={{
                    name: "The last article",
                    url: "#",
                }}
            />
            <StoryHeading>Only next</StoryHeading>
            <NextPrevious
                accessibleTitle={t("More Articles")}
                nextItem={{
                    name: "The next article",
                    url: "#",
                }}
            />
        </StoryContent>
    );
});
