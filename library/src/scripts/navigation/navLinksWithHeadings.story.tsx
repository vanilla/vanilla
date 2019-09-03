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
import NavLinksWithHeadings from "@library/navigation/NavLinksWithHeadings";
import { navLinksWithHeadingsData } from "@library/navigation/navLinksWithHeadings.storyData";

const story = storiesOf("Site Nav", module);

const data = navLinksWithHeadingsData;

story.add("Nav Links with Headings", () => {
    return (
        <StoryContent>
            <NavLinksWithHeadings
                title={t("Browse Articles by Category")}
                accessibleViewAllMessage={t(`View all articles from category: "<0/>".`)}
                {...data}
                depth={2}
                ungroupedTitle={t("Other Articles")}
                ungroupedViewAllUrl={data.ungroupedViewAllUrl}
            />
        </StoryContent>
    );
});
