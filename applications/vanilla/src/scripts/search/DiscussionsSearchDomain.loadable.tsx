/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getGlobalSearchSorts } from "@library/search/SearchFormContextProvider";
import { SearchService } from "@library/search/SearchService";
import { TypeDiscussionsIcon } from "@library/icons/searchIcons";
import { ISearchForm, ISearchRequestQuery } from "@library/search/searchTypes";
import { IDiscussionSearchTypes } from "@vanilla/addon-vanilla/search/discussionSearchTypes";
import { t } from "@vanilla/i18n";
import flatten from "lodash-es/flatten";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { SearchFilterPanelDiscussions } from "@vanilla/addon-vanilla/search/SearchFilterPanelDiscussions";
import { SearchFilterPanelComments } from "@vanilla/addon-vanilla/search/SearchFilterPanelComments";
import CollapseCommentsSearchMeta from "@vanilla/addon-vanilla/search/CollapseCommentsSearchMeta";
import { notEmpty } from "@vanilla/utils";
import SearchDomain from "@library/search/SearchDomain";
import { getSiteSection } from "@library/utility/appUtils";
import { dateRangeToString } from "@library/search/SearchUtils";

class DiscussionsSearchDomain extends SearchDomain<IDiscussionSearchTypes & { discussionID: string }> {
    public key = "discussions";
    public sort = 1;

    public get name() {
        return t("Discussions");
    }

    public icon = (<TypeDiscussionsIcon />);

    public recordTypes = ["discussion", "comment"];

    public getAllowedFields() {
        return [
            "name",
            "startDate",
            "endDate",
            "authors",
            "tagsOptions",
            "tagOperator",
            "categoryOption",
            "categoryOptions",
            "followedCategories",
            "includeChildCategories",
            "includeArchivedCategories",
            "discussionID",
            "types",
        ];
    }

    public PanelComponent = SearchFilterPanelDiscussions;

    public transformFormToQuery = (form: ISearchForm<IDiscussionSearchTypes & { discussionID: string }>) => {
        let query: ISearchRequestQuery<IDiscussionSearchTypes & { discussionID: string }> = {
            ...form,
            expand: ["insertUser", "breadcrumbs", "image", "excerpt", "-body"],
        };

        if (form.types) {
            const supportedTypes = flatten(CommunityPostTypeFilter.postTypes.map((type) => type.values));
            query.types = form.types.filter((type) => supportedTypes.includes(type));
        }

        if (form.discussionID && typeof parseInt(form.discussionID) === "number") {
            query.recordTypes = ["comment"]; // Include only comment record types.
            query.scope = "site";
            query.collapse = false;
        } else {
            if (form.authors) {
                query.insertUserIDs =
                    form.authors && form.authors.length
                        ? form.authors.map((author) => author.value as number)
                        : undefined;
                delete form.authors;
            }
        }

        if (query.tagsOptions) {
            query.tags = query.tagsOptions.map((tag: any) => tag?.tagCode ?? tag?.tagName).filter(notEmpty);
        }

        if (query.categoryOptions) {
            query.categoryIDs = query.categoryOptions.map((option) => option.value as number);
            // These are not allowed parameters
            delete query.categoryOptions;
            delete query.categoryOption;
        } else if (query.categoryOption) {
            query.categoryID = query.categoryOption.value as number;
        }

        const siteSection = getSiteSection();
        const siteSectionCategoryID = siteSection.attributes.categoryID;
        /**
         * query["categoryIDs"] could be a populated array, an empty array, or undefined
         */
        const hasCategoryIDs = !!(query["categoryIDs"] && query["categoryIDs"].length);
        if (!("categoryID" in query) && !hasCategoryIDs && siteSectionCategoryID > 0) {
            query.categoryID = siteSectionCategoryID;
            query.includeChildCategories = true;
        }

        query.dateInserted = dateRangeToString({ start: form.startDate, end: form.endDate });
        query.startDate = undefined;
        query.endDate = undefined;

        return query;
    };

    public defaultFormValues: Partial<ISearchForm<IDiscussionSearchTypes>> = {
        followedCategories: false,
        includeChildCategories: false,
        includeArchivedCategories: false,
        types: [],
    };

    public get sortValues() {
        const sorts = getGlobalSearchSorts();
        if (SearchService.supportsExtensions()) {
            sorts.push(
                {
                    content: t("Top"),
                    value: "-score",
                },
                {
                    content: t("Hot"),
                    value: "-hot",
                },
            );
        }
        return sorts;
    }

    public getSpecificRecordID = (form: ISearchForm<IDiscussionSearchTypes & { discussionID: string }>) =>
        !!form.discussionID && typeof form.discussionID === "number" ? parseInt(form.discussionID) : undefined;

    public SpecificRecordPanelComponent = SearchFilterPanelComments;
    public SpecificRecordComponent = CollapseCommentsSearchMeta;
}

const LOADABLE_DISCUSSIONS_SEARCH_DOMAIN = new DiscussionsSearchDomain();

export default LOADABLE_DISCUSSIONS_SEARCH_DOMAIN;
