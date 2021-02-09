/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SearchService } from "@library/search/SearchService";
import { t, onReady } from "@vanilla/library/src/scripts/utility/appUtils";
import { TypePlacesIcon, TypeCategoriesIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { ISearchForm, ISearchResult } from "@vanilla/library/src/scripts/search/searchTypes";
import { IPlaceSearchTypes } from "@dashboard/components/placeSearchType";
import PlacesSearchFilterPanel from "@dashboard/components/panels/PlacesSearchFilterPanel";
import Result from "@vanilla/library/src/scripts/result/Result";
import flatten from "lodash/flatten";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";
import { PLACES_CATEGORY_TYPE, PLACES_DOMAIN_NAME } from "@vanilla/library/src/scripts/search/searchConstants";
import { ResultMeta } from "@vanilla/library/src/scripts/result/ResultMeta";

export function PlacesResultMeta(props: { searchResult: Partial<ISearchResult> }) {
    const { searchResult } = props;
    const { type, counts } = searchResult;

    // Do not apply all of the same result meta.
    return <ResultMeta type={type} counts={counts} />;
}

export function registerPlaceSearchDomain() {
    onReady(() => {
        SearchService.addPluggableDomain({
            key: PLACES_DOMAIN_NAME,
            name: t("Places"),
            sort: 3,
            icon: <TypePlacesIcon />,
            getName: () => {
                const subTypes = SearchService.getSubTypes().filter((subType) => subType.domain === PLACES_DOMAIN_NAME);
                if (subTypes.length === 1) {
                    return subTypes[0].label;
                }
                return t("Places");
            },
            getAllowedFields: () => {
                return ["description"];
            },
            transformFormToQuery: (form: ISearchForm<IPlaceSearchTypes>) => {
                const query = {
                    query: form.query || "",
                    description: form.description || "",
                    name: form.name || "",
                    types: form.types || flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
                };

                return query;
            },
            getRecordTypes: () => {
                return ["category"];
            },
            PanelComponent: PlacesSearchFilterPanel,
            resultHeader: null,
            getDefaultFormValues: () => {
                return {
                    excerpt: "",
                    types: flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
                };
            },
            getSortValues: () => {
                return [
                    {
                        content: "Best Match",
                        value: "relevance",
                    },
                    {
                        content: "Name",
                        value: "name",
                    },
                ];
            },
            isIsolatedType: () => false,
            ResultComponent: Result,
            MetaComponent: PlacesResultMeta,
        });

        SearchService.addSubType({
            domain: PLACES_DOMAIN_NAME,
            label: t("Categories"),
            icon: <TypeCategoriesIcon />,
            recordType: "category",
            type: PLACES_CATEGORY_TYPE,
        });
    });
}
