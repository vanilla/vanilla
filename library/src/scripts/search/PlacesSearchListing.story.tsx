/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { PlacesSearchListingContainer, IPlacesSearchListingItem } from "@library/search/PlacesSearchListingContainer";
import { PlacesListingPlaceHolder } from "@library/search/PlacesListingPlaceHolder";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import { TypeCategoriesIcon, TypeGroupsIcon, TypeKnowledgeBaseIcon } from "@library/icons/searchIcons";
import { PLACES_CATEGORY_TYPE, PLACES_GROUP_TYPE, PLACES_KNOWLEDGE_BASE_TYPE } from "./searchConstants";
import { t } from "@vanilla/i18n";

export default {
    title: "Search/PlacesListing",
};

PLACES_SEARCH_DOMAIN.addSubType({
    label: t("Categories"),
    icon: <TypeCategoriesIcon />,
    type: PLACES_CATEGORY_TYPE,
});

PLACES_SEARCH_DOMAIN.addSubType({
    label: t("Groups"),
    icon: <TypeGroupsIcon />,
    type: PLACES_GROUP_TYPE,
});

PLACES_SEARCH_DOMAIN.addSubType({
    label: t("Knowledge Bases"),
    icon: <TypeKnowledgeBaseIcon />,
    type: PLACES_KNOWLEDGE_BASE_TYPE,
});

const items: IPlacesSearchListingItem[] = [
    {
        name: "General Help",
        type: "category",
        url: "/",
    },
    {
        name: "Knowledge Help",
        type: "knowledgeBase",
        url: "/",
    },
    {
        name: "Knowledge H",
        type: "knowledgeBase",
        url: "/",
    },
    {
        name: "Group A",
        type: "group",
        url: "/",
    },

    {
        name: "Group B",
        type: "group",
        url: "/",
    },

    {
        name: "Group C",
        type: "group",
        url: "/",
    },

    {
        name: "General 1",
        type: "category",
        url: "/",
    },
    {
        name: "General 2",
        type: "category",

        url: "/",
    },
];

export function PlacesSearch() {
    return <PlacesSearchListingContainer items={items} />;
}

export function PlaceHolder() {
    return <PlacesListingPlaceHolder />;
}
