/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SearchFormContextProvider } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { TypeDiscussionsIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { ISearchForm } from "@vanilla/library/src/scripts/search/searchTypes";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import { t } from "@vanilla/i18n";
import { onReady } from "@vanilla/library/src/scripts/utility/appUtils";
import flatten from "lodash/flatten";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { SearchFilterPanelCommunity } from "@vanilla/addon-vanilla/search/SearchFilterPanelCommunity";

export function registerCommunitySearchDomain() {
    onReady(() => {
        SearchFormContextProvider.addPluggableDomain({
            key: "discussions",
            name: t("Discussions"),
            icon: <TypeDiscussionsIcon />,
            getAllowedFields: () => {
                return [
                    // "tags", Not implemented for now.
                    "categoryID",
                    "followedCategories",
                    "includeChildCategories",
                    "includeArchivedCategories",
                ];
            },
            transformFormToQuery: (form: ISearchForm<ICommunitySearchTypes>) => {
                const query = {
                    ...form,
                };

                if (query.categoryOption) {
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
                    types: flatten(CommunityPostTypeFilter.postTypes.map(type => type.values)),
                };
            },
        });

        SearchFormContextProvider.addSubType({
            label: t("Discussions"),
            icon: <TypeDiscussionsIcon />,
            recordType: "discussion",
            type: "discussion",
        });

        SearchFormContextProvider.addSubType({
            label: t("Comment"),
            icon: <TypeDiscussionsIcon />,
            recordType: "comment",
            type: "comment",
        });
    });
}
