/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { placesSearchListingClasses } from "@library/search/placesSearchListing.styles";
import { SearchService } from "@library/search/SearchService";
import { TypeQuestionIcon } from "@library/icons/searchIcons";
import { buttonUtilityClasses } from "@library/forms/buttonStyles";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";

export interface IPlacesSearchListingItem {
    name: string;
    type?: string;
    url: string;
    className?: string;
    embedLinkClassName?: string;
}

interface IPlacesSearchListingContainer {
    items: IPlacesSearchListingItem[];
}

export function PlacesSearchListingItem(props: IPlacesSearchListingItem) {
    const { name, type, url, className, embedLinkClassName } = props;

    const icon = SearchService.getSubType(type || "")?.icon || <TypeQuestionIcon />;

    const classes = placesSearchListingClasses();
    return (
        <li key={name} className={className}>
            <SmartLink
                style={{ fontSize: "small" }}
                to={url}
                tabIndex={0}
                className={classNames(embedLinkClassName, { [`${classes.link}`]: true })}
            >
                <span className={classNames(classes.buttonIconContainer, buttonUtilityClasses().buttonIconRightMargin)}>
                    {icon}
                </span>
                {name}
            </SmartLink>
        </li>
    );
}

export function PlacesSearchListingContainer(props: IPlacesSearchListingContainer) {
    const { items } = props;

    const classes = placesSearchListingClasses();
    return (
        <ul className={classes.container}>
            {items.map((item) => {
                return <PlacesSearchListingItem key={item.name} {...item} />;
            })}
        </ul>
    );
}
