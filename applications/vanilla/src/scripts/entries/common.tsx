/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SearchFormContextProvider } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { onReady, t } from "@vanilla/library/src/scripts/utility/appUtils";
import { TypeDiscussionsIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { SearchFilterPanelDiscussions } from "@vanilla/library/src/scripts/search/panels/FilterPanelDiscussions";
import { ISearchForm } from "@vanilla/library/src/scripts/search/searchTypes";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";

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
            return ["discussion"];
        },
        PanelComponent: SearchFilterPanelDiscussions,
        getDefaultFormValues: () => {
            return {
                followedCategories: false,
                includeChildCategories: false,
                includeArchivedCategories: false,
            };
        },
    });
});
