/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getGlobalSearchSorts } from "@library/search/SearchFormContextProvider";
import { SearchService } from "@library/search/SearchService";
import { TypeDiscussionsIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { ISearchForm } from "@vanilla/library/src/scripts/search/searchTypes";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import { t } from "@vanilla/i18n";
import { onReady } from "@vanilla/library/src/scripts/utility/appUtils";
import flatten from "lodash/flatten";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { SearchFilterPanelCommunity } from "@vanilla/addon-vanilla/search/SearchFilterPanelCommunity";
import Result from "@vanilla/library/src/scripts/result/Result";
import { SearchFilterPanelComments } from "@vanilla/addon-vanilla/search/SearchFilterPanelComments";
import CollapseCommentsSearchMeta from "@vanilla/addon-vanilla/search/CollapseCommentsSearchMeta";
import { notEmpty } from "@vanilla/utils";

export function registerCommunitySearchDomain() {
    onReady(() => {
        SearchService.addPluggableDomain({
            key: "discussions",
            name: t("Discussions"),
            sort: 1,
            icon: <TypeDiscussionsIcon />,
            getAllowedFields: () => {
                return [
                    "tagsOptions",
                    "categoryOption",
                    "categoryOptions",
                    "followedCategories",
                    "includeChildCategories",
                    "includeArchivedCategories",
                    "discussionID",
                ];
            },
            transformFormToQuery: (form: ISearchForm<ICommunitySearchTypes>) => {
                const query: ISearchForm<ICommunitySearchTypes> = {
                    ...form,
                };

                if (form.discussionID && typeof parseInt(form.discussionID) === "number") {
                    query.recordTypes = ["comment"]; // Include only comment record types.
                    query.scope = "site";
                    query.collapse = false;
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

                return query;
            },
            getRecordTypes: () => {
                return ["discussion", "comment"];
            },
            PanelComponent: SearchFilterPanelCommunity,
            getDefaultFormValues: () => {
                return {
                    followedCategories: false,
                    includeChildCategories: false,
                    includeArchivedCategories: false,
                    types: flatten(CommunityPostTypeFilter.postTypes.map((type) => type.values)),
                };
            },
            getSortValues: () => {
                const sorts = getGlobalSearchSorts();
                if (SearchService.supportsExtensions()) {
                    sorts.push(
                        {
                            content: "Top",
                            value: "-score",
                        },
                        {
                            content: "Hot",
                            value: "-hot",
                        },
                    );
                }
                return sorts;
            },
            isIsolatedType: () => false,
            ResultComponent: Result,
            hasSpecificRecord: (form: ISearchForm<ICommunitySearchTypes>) =>
                form.discussionID && typeof form.discussionID === "number",
            getSpecificRecord: (form: ISearchForm<ICommunitySearchTypes>) => form.discussionID,
            SpecificRecordPanel: SearchFilterPanelComments,
            SpecificRecordComponent: CollapseCommentsSearchMeta,
            showSpecificRecordCrumbs: () => false,
        });

        SearchService.addSubType({
            label: t("Discussions"),
            icon: <TypeDiscussionsIcon />,
            recordType: "discussion",
            type: "discussion",
        });

        SearchService.addSubType({
            label: t("Comment"),
            icon: <TypeDiscussionsIcon />,
            recordType: "comment",
            type: "comment",
        });
    });
}
