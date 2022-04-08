/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CategoriesWidgetPreview } from "@library/categoriesWidget/CategoriesWidget.preview";
import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";

export default {
    title: "Widgets/CategoriesWidget",
};

export function CategoriesPreview() {
    return (
        <>
            <StoryHeading>
                Categories Widget Preview, e.g. in Layout editor/overview pages (Different variants of this widget can
                be seen under HomeWidget)
            </StoryHeading>
            <CategoriesWidgetPreview />
        </>
    );
}
