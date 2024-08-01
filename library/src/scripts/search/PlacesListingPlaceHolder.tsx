/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { placesSearchListingClasses } from "@library/search/placesSearchListing.styles";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";

interface IProps {
    count?: number;
}

export function PlacesListingPlaceHolder(props: IProps) {
    const { count = 8 } = props;

    const classes = placesSearchListingClasses();
    return (
        <ul style={{ paddingTop: "10px" }} className={classes.container}>
            {Array.from(new Array(count)).map((_, i) => {
                return (
                    <li style={{ display: "flex", alignItems: "center", paddingBottom: "10px" }} key={i}>
                        <LoadingRectangle height={16} width={16} style={{ marginRight: 10, borderRadius: "50%" }} />
                        <LoadingRectangle height={12} width={100} style={{ marginRight: 10 }} />
                    </li>
                );
            })}
        </ul>
    );
}
