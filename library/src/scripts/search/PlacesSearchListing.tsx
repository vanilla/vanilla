/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LoadStatus } from "@library/@types/api/core";
import { PlacesListingPlaceHolder } from "@library/search/PlacesListingPlaceHolder";
import { PLACES_DOMAIN_NAME } from "@library/search/searchConstants";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { PlacesSearchListingContainer } from "@library/search/PlacesSearchListingContainer";
import { useSearchForm } from "@library/search/SearchContext";

interface IProps {}

export default function PlacesSearchListing(props: IProps) {
    const { domainSearchResponse } = useSearchForm<{}>();

    const response = domainSearchResponse[PLACES_DOMAIN_NAME];
    // To prevent situation when component is mounted but response not yet
    // available
    const status = (response && response.status) || LoadStatus.PENDING;

    switch (status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            return <PlacesListingPlaceHolder count={8} />;
        case LoadStatus.ERROR:
            return <CoreErrorMessages error={response.error} />;
        case LoadStatus.SUCCESS:
            const itemList = response.data?.results || [];
            const items = itemList?.map((element) => {
                const { type, name, url } = element;
                return { type, name, url };
            });
            return <PlacesSearchListingContainer items={items} />;
    }
}
