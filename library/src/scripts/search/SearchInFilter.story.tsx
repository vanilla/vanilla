/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { useState } from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { t } from "@vanilla/i18n/src";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { ISearchInButton, SearchInFilter } from "@library/search/SearchInFilter";
import SearchContext from "@library/contexts/SearchContext";
import { MemoryRouter } from "react-router";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { Icon } from "@vanilla/icons";

interface IProps {
    activeItem?: string;
    filters?: ISearchInButton[];
    endFilters?: ISearchInButton[]; // At the end, separated by vertical line
    message?: string;
}

const dummmyFilters: ISearchInButton[] = [
    {
        label: t("All"),
        icon: <Icon icon="search-all" />,
        data: "all",
    },
    {
        label: t("Discussions"),
        icon: <Icon icon={"search-discussions"} />,
        data: "discussions",
    },
    {
        label: t("Articles"),
        icon: <Icon icon={"search-articles"} />,
        data: "articles",
    },
    {
        label: t("Categories & Groups"),
        icon: <Icon icon="search-categories" />,
        data: "categoriesAndGroups",
    },
];

const dummmyEndFilters: ISearchInButton[] = [
    {
        label: t("Members"),
        icon: <Icon icon="search-members" />,
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
function SearchFilterStory(props: IProps) {
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

export const NoMembers = storyWithConfig({}, () => <SearchFilterStory activeItem={"Groups"} endFilters={[]} />);
export const NotRendered = storyWithConfig({}, () => (
    <SearchFilterStory
        message={
            "This page should stay empty, we don't want to render the component unless there are more than 1 filters."
        }
        filters={[]}
        endFilters={[]}
    />
));

export function SearchFilter() {
    return <SearchFilterStory />;
}
