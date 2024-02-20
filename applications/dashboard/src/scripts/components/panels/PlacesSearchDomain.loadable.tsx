/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { TypePlacesIcon } from "@library/icons/searchIcons";
import { IPlacesSearchResult, ISearchForm } from "@library/search/searchTypes";
import { IPlacesSearchTypes } from "@dashboard/components/panels/placesSearchTypes";
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

class PlacesSearchDomain extends SearchDomain<IPlacesSearchTypes> {
    public key = "places";
    public sort = 3;

    get name() {
        return this.subTypes.length === 1 ? this.subTypes[0].label : t("Places");
    }

    public icon = (<TypePlacesIcon />);

    public getAllowedFields() {
        return ["name", "description", "types"];
    }

    public transformFormToQuery = function (form: ISearchForm<IPlacesSearchTypes>) {
        const supportedTypes = flatten(PlacesSearchTypeFilter.searchTypes.map((type) => type.values));
        return {
            ...form,
            types: form.types ? supportedTypes.filter((type) => supportedTypes.includes(type)) : supportedTypes,
            expand: ["breadcrumbs", "image", "excerpt", "-body"],
            recordTypes: undefined,
        };
    };

    public recordTypes = ["category"];

    public PanelComponent = PlacesSearchFilterPanel;

    public defaultFormValues: Partial<ISearchForm<IPlacesSearchTypes>> = {
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
