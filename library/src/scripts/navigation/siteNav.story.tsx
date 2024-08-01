/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import SiteNav from "@library/navigation/SiteNav";
import { STORY_SITE_NAV_ACTIVE_RECORD, STORY_SITE_NAV_ITEMS } from "@library/navigation/siteNav.storyData";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";

export default {
    title: "Navigation",
    parameters: {
        chromatic: {
            // viewports: [1400, 400],
        },
    },
};

export function SiteNavCollapser() {
    return (
        <StoryContent>
            <StoryHeading>Guide</StoryHeading>
            <SiteNavProvider categoryRecordType="knowledgeCategory">
                <SiteNav activeRecord={STORY_SITE_NAV_ACTIVE_RECORD} clickableCategoryLabels={true} collapsible={true}>
                    {STORY_SITE_NAV_ITEMS}
                </SiteNav>
            </SiteNavProvider>
            <StoryHeading>Help</StoryHeading>
            <SiteNavProvider categoryRecordType="article">
                <SiteNav activeRecord={STORY_SITE_NAV_ACTIVE_RECORD} collapsible={true}>
                    {STORY_SITE_NAV_ITEMS}
                </SiteNav>
            </SiteNavProvider>
        </StoryContent>
    );
}
