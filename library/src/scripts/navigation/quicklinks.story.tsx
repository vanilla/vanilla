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

export default {
    title: "Components/QuickLinks",
    parameters: {},
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

export const Standard = storyWithConfig({}, () => (
    <StoryContent>
        <StoryHeading depth={1}>Quick Links</StoryHeading>
        <QuickLinksView title="Quick Links" links={dummyData} />
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
                <QuickLinksView title="Quick Links" links={dummyData} />
            </StoryContent>
        );
    },
);

export const ListItemColors = storyWithConfig(
    {
        themeVars: {
            quickLinks: {
                listItem: {
                    fgColor: {
                        default: "#03526C",
                        allStates: "#013D51",
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
                <QuickLinksView title="Quick Links" links={dummyData} />
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
                <QuickLinksView title="Quick Links" links={dummyData} />
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
                title: {
                    font: {
                        color: "#07ba82",
                        size: 22,
                    },
                },
                listItem: {
                    font: {
                        size: 16,
                    },
                    listSeparation: ListSeparation.SEPARATOR,
                    fgColor: {
                        default: "#fff",
                        allStates: "#fff",
                    },
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
            <QuickLinksView title="Quick Links With Borders" links={dummyData} />
        </StoryContent>
    ),
);
