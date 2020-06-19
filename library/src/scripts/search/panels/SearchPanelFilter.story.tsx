/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { SearchFilterAll } from "@library/icons/searchIcons";

import { StoryHeading } from "@library/storybook/StoryHeading";
import { SearchFilterPanelArticles } from "@library/search/panels/FilterPanelArticles";
import { SearchFilterPanelCategoriesAndGroups } from "@library/search/panels/FilterPanelCategoriesAndGroups";
import { SearchFilterPanelDiscussions } from "@library/search/panels/FilterPanelDiscussions";

interface IProps {
    message?: string;
}

export default {
    title: "Search/Panel Filter",
};

/**
 * Implements the search bar component
 */
export function SearchPanelFilter(props: IProps) {
    return (
        <div style={{ width: "500px", margin: "auto" }}>
            <StoryHeading>Search Panel Filter - All</StoryHeading>
            <SearchFilterAll />

            <StoryHeading>Search Panel Filter - Articles</StoryHeading>
            <SearchFilterPanelArticles />

            <StoryHeading>Search Panel Filter - Categories & Groups</StoryHeading>
            <SearchFilterPanelCategoriesAndGroups />

            <StoryHeading>Search Panel Filter - Discussions</StoryHeading>
            <SearchFilterPanelDiscussions />
        </div>
    );
}
