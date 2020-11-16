/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { searchResultsClasses } from "@library/features/search/searchResultsStyles";
import Translate from "@library/content/Translate";
import Paragraph from "@library/layout/Paragraph";
import { t } from "@vanilla/i18n/src";
import { useLayout } from "@library/layout/LayoutContext";
import PanelWidget from "@library/layout/components/PanelWidget";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: any[];
    result: React.ComponentType<any>;
    emptyMessage?: string;
    headingLevel?: 2 | 3;
    ResultWrapper?: React.ComponentType<any>;
    rel?: string;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default function ResultList(props: IProps) {
    const {
        className,
        searchTerm,
        results,
        emptyMessage = t("No results found."),
        headingLevel,
        ResultWrapper,
        result,
    } = props;

    const hasResults = results && results.length > 0;

    let content;
    const classes = searchResultsClasses(useLayout().mediaQueries);

    if (hasResults) {
        const Result = result;
        content = results.map((result, i) => {
            return <Result {...result} key={i} headingLevel={headingLevel} rel={props.rel} />;
        });
    } else {
        let message =
            searchTerm === undefined || searchTerm === "" ? (
                emptyMessage
            ) : (
                <Translate source="No results for '<0/>'." c0={searchTerm} />
            );

        content = (
            <PanelWidget>
                <Paragraph className={classNames("searchResults-noResults", classes.noResults)}>{message}</Paragraph>
            </PanelWidget>
        );
    }

    if (ResultWrapper) {
        return <ResultWrapper>{content}</ResultWrapper>;
    } else {
        const Tag = hasResults ? `ul` : `div`;
        return <Tag className={classNames("searchResults", classes.root, className)}>{content}</Tag>;
    }
}
