/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpers";
import { ListSeparation } from "@library/styles/cssUtilsTypes";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import PanelWidget from "@library/layout/components/PanelWidget";

export default {
    title: "Components/QuickLinks",
    parameters: {},
    includeStories: ["Standard", "ListSeparatorBorder", "ListItemColors", "Borders", "LineSeparator", "InPanel"],
};

const dummyData = [
    {
        id: "categories",
        name: "Categories",
        url: "/categories",
        isHidden: false,
        children: [],
    },
    {
        id: "discussions",
        name: "Discussions",
        url: "/discussions",
        isHidden: false,
        children: [],
    },
    {
        id: "activity",
        name: "Activity",
        url: "/activity",
        isHidden: false,
        children: [],
    },
    {
        id: "bookmarks",
        name: "My Bookmarks",
        url: "/bookmarks",
        count: 4,
        isHidden: false,
        children: [],
    },
    {
        id: "drafts",
        name: "My Drafts",
        url: "/drafts",
        count: 30,
        isHidden: false,
        children: [],
    },
    {
        id: "unanswered",
        name: "Unanswered",
        url: "/unanswered",
        count: 10,
        isHidden: false,
        children: [],
    },
    {
        id: "isHidden",
        name: "Hidden Url",
        url: "/isHidden/secret",
        count: 100,
        isHidden: true,
        children: [],
    },
];

export function StoryQuickLinks(props: { title?: string }) {
    return <QuickLinksView {...props} links={dummyData} activePath={dummyData[0].url} />;
}

export const Standard = storyWithConfig({}, () => (
    <StoryContent>
        <StoryHeading depth={1}>Quick Links</StoryHeading>
        <StoryQuickLinks title="Quick Links" />
    </StoryContent>
));

export const ListSeparatorBorder = storyWithConfig(
    {
        themeVars: {
            quickLinks: {
                listItem: {
                    listSeparation: ListSeparation.BORDER,
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Border Separator</StoryHeading>
                <StoryQuickLinks title="Quick Links" />
            </StoryContent>
        );
    },
);

export const ListItemColors = storyWithConfig(
    {
        themeVars: {
            quickLinks: {
                listItem: {
                    font: {
                        color: "#03526C",
                    },
                    fontState: {
                        color: "#013D51",
                    },
                    listSeparation: ListSeparation.SEPARATOR,
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Border Separator</StoryHeading>
                <StoryQuickLinks title="Quick Links" />
            </StoryContent>
        );
    },
);

export const LineSeparator = storyWithConfig(
    {
        themeVars: {
            quickLinks: {
                listItem: {
                    listSeparation: ListSeparation.SEPARATOR,
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Line Separator</StoryHeading>
                <StoryQuickLinks title="Quick Links" />
            </StoryContent>
        );
    },
);

export const Borders = storyWithConfig(
    {
        themeVars: {
            quickLinks: {
                box: {
                    background: {
                        color: "#071fba",
                    },
                    borderType: BorderType.BORDER,
                },
                listItem: {
                    font: {
                        size: 16,
                        color: "#fff",
                    },
                    fontState: {
                        color: "#fff",
                    },
                    listSeparation: ListSeparation.SEPARATOR,
                    spacing: {
                        vertical: 5,
                    },
                    padding: {
                        horizontal: 15,
                    },
                },
                count: {
                    font: {
                        color: "#07ba82",
                        weight: 700,
                    },
                },
            },
        },
    },
    () => (
        <StoryContent>
            <StoryHeading depth={1}>Quick Links</StoryHeading>
            <StoryQuickLinks title="Quick Links With Borders" />
        </StoryContent>
    ),
);

export const InPanel = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        return (
            <SectionTwoColumns
                mainTop={<PanelWidget />}
                secondaryTop={
                    <PanelWidget>
                        <StoryQuickLinks title="Quick Links" />
                        <QuickLinksView
                            title="More Quick Links"
                            links={[
                                {
                                    id: "home",
                                    name: "Home",
                                    url: "/",
                                    isHidden: false,
                                },
                                {
                                    id: "profile",
                                    name: "My Profile",
                                    url: "/profile",
                                    isHidden: false,
                                    children: [],
                                },
                                {
                                    id: "events",
                                    name: "Events",
                                    url: "/events",
                                    isHidden: false,
                                },
                            ]}
                        />
                        <QuickLinksView
                            title="Even More Quick Links"
                            links={[
                                {
                                    id: "developerDocs",
                                    name: "Developer Documentation",
                                    url: "/developer-docs",
                                    isHidden: false,
                                },
                                {
                                    id: "popular",
                                    name: "Most Popular",
                                    url: "/popular",
                                    isHidden: false,
                                },
                            ]}
                        />
                    </PanelWidget>
                }
            ></SectionTwoColumns>
        );
    },
);
