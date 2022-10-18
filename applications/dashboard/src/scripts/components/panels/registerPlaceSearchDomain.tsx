/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ISearchDomain, SearchService } from "@library/search/SearchService";
import { t, onReady } from "@library/utility/appUtils";
import { TypePlacesIcon, TypeCategoriesIcon } from "@library/icons/searchIcons";
import { IPlacesSearchResult, ISearchResult } from "@library/search/searchTypes";
import { IPlaceSearchTypes } from "@dashboard/components/placeSearchType";
import PlacesSearchFilterPanel from "@dashboard/components/panels/PlacesSearchFilterPanel";
import flatten from "lodash/flatten";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";
import { PLACES_CATEGORY_TYPE, PLACES_DOMAIN_NAME } from "@library/search/searchConstants";
import { ResultMeta } from "@library/result/ResultMeta";

export function PlacesResultMeta(props: { searchResult: Partial<IPlacesSearchResult> }) {
    const { searchResult } = props;
    const { type, counts } = searchResult;

    // Do not apply all of the same result meta.
    return <ResultMeta type={type} counts={counts} />;
}

export function registerPlaceSearchDomain() {
    onReady(() => {
        const placesSearchDomain: ISearchDomain<IPlaceSearchTypes> = {
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
            transformFormToQuery: (form) => {
                return {
                    types: form.types || flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
                };
            },
            getRecordTypes: () => {
                return ["category"];
            },
            PanelComponent: PlacesSearchFilterPanel,
            resultHeader: null,
            getDefaultFormValues: () => {
                return {
                    description: "",
                    types: flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
                };
            },
            getSortValues: () => {
                return [
                    {
                        content: t("Best Match"),
                        value: "relevance",
                    },
                    {
                        content: t("Name"),
                        value: "name",
                    },
                ];
            },
            isIsolatedType: () => false,
            MetaComponent: PlacesResultMeta,
        };

        SearchService.addPluggableDomain(placesSearchDomain);

        SearchService.addSubType({
            domain: PLACES_DOMAIN_NAME,
            label: t("Categories"),
            icon: <TypeCategoriesIcon />,
            recordType: "category",
            type: PLACES_CATEGORY_TYPE,
        });
    });
}
