/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import CategoryPicker from "@library/forms/select/CategoryPicker";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";

export default {
    title: "Components/Category picker",
    parameters: {},
};

const dummyProps = {
    defaultItem: {
        label: "Select a category ...",
        value: "all",
    },
    items: [
        {
            value: "1",
            label: "First category",
            depth: 1,
            breadcrumbs: [
                { name: "Home", url: "/" },
                { name: "First category", url: "#" },
            ],
            description: "Description for first category",
        },
        {
            value: "2",
            label: "Second category",
            depth: 2,
            disabled: true,
            breadcrumbs: [
                { name: "Home", url: "/" },
                { name: "First category", url: "#" },
                { name: "Second category", url: "#" },
            ],
            description: "Description for second category",
        },
        {
            value: "3",
            label: "Third category",
            depth: 3,
            breadcrumbs: [
                { name: "Home", url: "/" },
                { name: "First category", url: "#" },
                { name: "Second category", url: "#" },
                { name: "Third category", url: "#" },
            ],
            description: "Description for third category",
        },
    ],
};

const dummyPropsCategoryInfoOnly = {
    categoryInfoOnly: true,
    items: [
        {
            value: "3",
            label: "Third category",
            depth: 3,

            description: "Description for third category",
        },
    ],
    initialValue: "3",
};

export function CategoryPickerDropdown() {
    return (
        <div style={{ maxWidth: 400, margin: 60 }}>
            <StoryHeading depth={1}>Category Picker</StoryHeading>
            <CategoryPicker {...dummyProps} />
        </div>
    );
}

export const OnlyCategoryInfo = storyWithConfig({}, () => (
    <div style={{ maxWidth: 400, margin: 60 }}>
        <StoryHeading depth={1}>No picker, posting discussion already being in category</StoryHeading>
        <CategoryPicker {...dummyPropsCategoryInfoOnly} />
    </div>
));
