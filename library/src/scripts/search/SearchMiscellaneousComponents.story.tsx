/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { ISearchSort, SortAndPaginationInfo, ISearchSortAndPages } from "@library/search/SortAndPaginationInfo";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { t } from "@vanilla/i18n/src";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { ILinkPages } from "@library/navigation/SimplePagerModel";

interface IProps extends ISearchSortAndPages {
    message?: string;
}

export default {
    title: "Search/MiscellaneousComponents",
};

const dummySortData: ISearchSort = {
    options: [
        {
            value: "1",
            name: t("Last Updated"),
        },
        {
            value: "2",
            name: t("Date Created"),
        },
        {
            value: "3",
            name: t("Last Commented"),
        },
    ] as ISelectBoxItem[],
};

const dummyPages: ILinkPages = {
    currentPage: 3,
    limit: 10,
    total: 158,
};

function SearchMiscellaneousComponents(props: IProps) {
    const { sort, pages, message } = props;
    return (
        <>
            {message && <StoryParagraph>{message}</StoryParagraph>}
            <SortAndPaginationInfo sort={sort} pages={pages} />
        </>
    );
}

export const NoSort = storyWithConfig({}, () => (
    <SearchMiscellaneousComponents
        sort={{ options: [] }}
        pages={dummyPages}
        message={"No sort, only pagination info"}
    />
));
export const NoPaginationInfo = storyWithConfig({}, () => (
    <SearchMiscellaneousComponents message={"No pagination info, only sort"} sort={dummySortData} />
));
export const NoRender = storyWithConfig({}, () => (
    <SearchMiscellaneousComponents message={"Does not render, no data"} sort={{ options: [] }} />
));
export const BigTotal = storyWithConfig({}, () => (
    <SearchMiscellaneousComponents
        message={"Big total"}
        pages={{ ...dummyPages, total: 9595959595 }}
        sort={dummySortData}
    />
));
