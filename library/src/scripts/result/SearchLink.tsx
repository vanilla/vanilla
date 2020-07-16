/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useContext } from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { searchResultClasses } from "@library/features/search/searchResultsStyles";
import classNames from "classnames";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    className?: string;
    url: string;
    label: string;
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default function SearchLink(props: IProps) {
    const { label, className, url } = props;

    return (
        <SmartLink
            to={url}
            className={classNames(searchResultClasses(useLayout().mediaQueries).afterExcerptLink, className)}
        >
            {label}
        </SmartLink>
    );
}
