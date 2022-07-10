/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { PlacesSearchListingContainer, IPlacesSearchListingItem } from "@library/search/PlacesSearchListingContainer";
import { PlacesListingPlaceHolder } from "@library/search/PlacesListingPlaceHolder";

export default {
    title: "Search/PlacesListing",
};

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
