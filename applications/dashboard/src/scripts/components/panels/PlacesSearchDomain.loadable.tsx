/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";
import { t } from "@library/utility/appUtils";
import { TypePlacesIcon, TypeCategoriesIcon } from "@library/icons/searchIcons";
import { IPlacesSearchResult, ISearchForm, ISearchResult } from "@library/search/searchTypes";
import { IPlaceSearchTypes } from "@dashboard/components/placeSearchType";
import PlacesSearchFilterPanel from "@dashboard/components/panels/PlacesSearchFilterPanel";
import flatten from "lodash/flatten";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";
import { ResultMeta } from "@library/result/ResultMeta";
import SearchDomain from "@library/search/SearchDomain";

export function PlacesResultMeta(props: { searchResult: Partial<IPlacesSearchResult> }) {
    const { searchResult } = props;
    const { type, counts } = searchResult;

    // Do not apply all of the same result meta.
    return <ResultMeta type={type} counts={counts} />;
}

class PlacesSearchDomain extends SearchDomain<IPlaceSearchTypes> {
    public key = "places";
    public sort = 3;

    get name() {
        return this.subTypes.length === 1 ? this.subTypes[0].label : t("Places");
    }

    public icon = (<TypePlacesIcon />);

    public getAllowedFields() {
        return ["name", "description"];
    }

    public transformFormToQuery = function (form: ISearchForm<IPlaceSearchTypes>) {
        return {
            ...form,
            types: form.types ?? flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
            expand: ["breadcrumbs", "image", "excerpt", "-body"],
            recordTypes: undefined,
        };
    };

    public recordTypes = ["category"];

    public PanelComponent = PlacesSearchFilterPanel;

    public defaultFormValues = {
        description: "",
        types: flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values)),
    };

    public get sortValues() {
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
    }

    public MetaComponent = PlacesResultMeta;
}

const LOADABLE_PLACES_SEARCH_DOMAIN = new PlacesSearchDomain();

export default LOADABLE_PLACES_SEARCH_DOMAIN;
