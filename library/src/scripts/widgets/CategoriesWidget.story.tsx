/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { setMeta } from "@library/utility/appUtils";
import React from "react";
import CategoriesWidget from "@library/widgets/CategoriesWidget";
import { mockCategoriesDataWithHeadings } from "@library/widgets/CategoriesWidget.fixtures";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { WidgetItemContentType } from "@library/homeWidget/WidgetItemOptions";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type CategoriesWidgetItem from "./CategoriesWidget.Item";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpersBorders";

export default {
    title: "Widgets/CategoriesWidget",
};

setMeta("featureFlags.layoutEditor.categoryList.Enabled", true);

const queryClient = new QueryClient({});

const categoryOptions: CategoriesWidgetItem.Options = {
    description: {
        display: true,
    },
    followButton: {
        display: true,
    },
    metas: {
        display: {
            postCount: true,
            discussionCount: true,
            commentCount: true,
            followerCount: true,
            lastPostName: true,
            lastPostAuthor: true,
            lastPostDate: true,
            subcategories: true,
        },
    },
};

export function CategoryListWithHeadings() {
    return (
        <QueryClientProvider client={queryClient}>
            <StoryHeading>Categories Widget - List With Headings</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                categoryOptions={categoryOptions}
                containerOptions={{ displayType: WidgetContainerDisplayType.LIST }}
            />
            <StoryHeading>Categories Widget - List With Headings and Icons</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                categoryOptions={categoryOptions}
                itemOptions={{
                    contentType: WidgetItemContentType.TitleDescriptionIcon,
                    imagePlacement: "left",
                }}
                containerOptions={{ displayType: WidgetContainerDisplayType.LIST }}
            />
            <StoryHeading>Categories Widget - List With Headings and Images and Meta Items as Icons</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: WidgetItemContentType.TitleDescriptionImage }}
                categoryOptions={{ ...categoryOptions, metas: { ...categoryOptions.metas, asIcons: true } }}
                containerOptions={{ displayType: WidgetContainerDisplayType.LIST }}
            />
        </QueryClientProvider>
    );
}

export function CategoriesGridWithHeadings() {
    return (
        <QueryClientProvider client={queryClient}>
            <StoryHeading>Categories Widget - Grid With Headings and Items imageType as Icons</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: WidgetItemContentType.TitleDescriptionIcon }}
                categoryOptions={categoryOptions}
            />
            <StoryHeading>Categories Widget - Grid With Headings and Items imageType as Images</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: WidgetItemContentType.TitleDescriptionImage }}
                categoryOptions={categoryOptions}
            />
            <StoryHeading>Categories Widget - Grid With Headings and Items imageType as Background</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: WidgetItemContentType.TitleBackground }}
                categoryOptions={categoryOptions}
            />
        </QueryClientProvider>
    );
}

export const CategoryGridWithItemsWithNoBorderAndContentAlignedLeft = storyWithConfig(
    {
        themeVars: {
            homeWidgetItem: {
                options: {
                    box: {
                        borderType: BorderType.NONE,
                    },
                    alignment: "left",
                },
            },
        },
    },
    () => {
        return (
            <>
                <StoryHeading>
                    Categories Widget - Grid With Headings and Items are aligned left, do not have a border and image
                    type is Icon
                </StoryHeading>
                <CategoriesWidget
                    itemData={mockCategoriesDataWithHeadings}
                    itemOptions={{
                        contentType: WidgetItemContentType.TitleDescriptionIcon,
                    }}
                    categoryOptions={categoryOptions}
                />
            </>
        );
    },
);
