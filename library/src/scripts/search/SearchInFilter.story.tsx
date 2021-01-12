/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { useState } from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import {
    TypeAllIcon,
    TypeArticlesIcon,
    TypeCategoriesAndGroupsIcon,
    TypeDiscussionsIcon,
    TypeMemberIcon,
} from "@library/icons/searchIcons";
import { t } from "@vanilla/i18n/src";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { ISearchInButton, SearchInFilter } from "@library/search/SearchInFilter";
import SearchContext from "@library/contexts/SearchContext";
import { MemoryRouter } from "react-router";
import { MockSearchData } from "@library/contexts/DummySearchContext";

interface IProps {
    activeItem?: string;
    filters?: ISearchInButton[];
    endFilters?: ISearchInButton[]; // At the end, separated by vertical line
    message?: string;
}

const dummmyFilters: ISearchInButton[] = [
    {
        label: t("All"),
        icon: <TypeAllIcon />,
        data: "all",
    },
    {
        label: t("Discussions"),
        icon: <TypeDiscussionsIcon />,
        data: "discussions",
    },
    {
        label: t("Articles"),
        icon: <TypeArticlesIcon />,
        data: "articles",
    },
    {
        label: t("Categories & Groups"),
        icon: <TypeCategoriesAndGroupsIcon />,
        data: "categoriesAndGroups",
    },
];

const dummmyEndFilters: ISearchInButton[] = [
    {
        label: t("Members"),
        icon: <TypeMemberIcon />,
        data: "members",
    },
];

export default {
    title: "Search/Filters",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

/**
 * Implements the search bar component
 */
export function SearchFilter(props: IProps) {
    const { activeItem = "all", filters = dummmyFilters, endFilters = dummmyEndFilters, message } = props;
    const [data, setData] = useState(activeItem);

    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                {message && <StoryParagraph>{message}</StoryParagraph>}
                <SearchInFilter filters={filters} endFilters={endFilters} setData={setData} activeItem={data} />
            </SearchContext.Provider>
        </MemoryRouter>
    );
}

export const NoMembers = storyWithConfig({}, () => <SearchFilter activeItem={"Groups"} endFilters={[]} />);
export const NotRendered = storyWithConfig({}, () => (
    <SearchFilter
        message={
            "This page should stay empty, we don't want to render the component unless there are more than 1 filters."
        }
        filters={[]}
        endFilters={[]}
    />
));
