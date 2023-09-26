/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { setMeta } from "@library/utility/appUtils";
import React from "react";
import CategoriesWidget from "@library/categoriesWidget/CategoriesWidget";
import { mockCategoriesDataWithHeadings } from "@library/categoriesWidget/CategoriesWidget.fixtures";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";

export default {
    title: "Widgets/CategoriesWidget",
};

setMeta("featureFlags.layoutEditor.categoryList.Enabled", true);

export function CategoriesGridWithHeadings() {
    return (
        <>
            <StoryHeading>Categories Widget - List With Headings</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                containerOptions={{ displayType: WidgetContainerDisplayType.GRID }}
                itemOptions={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON }}
            />
        </>
    );
}

export function CategoryListWithHeadings() {
    return (
        <>
            <StoryHeading>Categories Widget - List With Headings</StoryHeading>
            <CategoriesWidget itemData={mockCategoriesDataWithHeadings} />
            <StoryHeading>Categories Widget - List With Headings and Icons</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON, imagePlacement: "left" }}
            />
            <StoryHeading>Categories Widget - List With Headings and Images</StoryHeading>
            <CategoriesWidget
                itemData={mockCategoriesDataWithHeadings}
                itemOptions={{ contentType: HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE }}
            />
        </>
    );
}
