/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { LoadStatus } from "@library/@types/api/core";
import SiteNav from "@library/navigation/SiteNav";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { siteNavStoryData } from "@library/navigation/siteNav.storyData";

const story = storiesOf("Navigation", module);

const data = siteNavStoryData;

story.add("Site Nav", () => {
    return (
        <>
            <StoryHeading depth={1}>Navigation</StoryHeading>
            <StoryHeading>Guide</StoryHeading>
            <SiteNavProvider categoryRecordType="knowledgeCategory">
                <SiteNav {...data} clickableCategoryLabels={true} collapsible={true}>
                    {data.navItems.data}
                </SiteNav>
            </SiteNavProvider>
            <StoryHeading>Help</StoryHeading>
            <SiteNavProvider categoryRecordType="article">
                <SiteNav {...data}>{data.navItems.data}</SiteNav>
            </SiteNavProvider>
        </>
    );
});
