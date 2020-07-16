/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { searchResultsClasses } from "@library/features/search/searchResultsStyles";
import Translate from "@library/content/Translate";
import Result, { IResult } from "@library/result/Result";
import Paragraph from "@library/layout/Paragraph";
import { t } from "@vanilla/i18n/src";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: any[];
    result?: React.ComponentClass;
    emptyMessage?: string;
    headingLevel?: 2 | 3;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default function ResultList(props: IProps) {
    const { className, searchTerm, results, result, emptyMessage = t("No results found."), headingLevel } = props;

    const hasResults = results && results.length > 0;
    let content;
    const classes = searchResultsClasses(useLayout().mediaQueries);

    if (hasResults) {
        const ResultComponent = result ?? Result;
        content = results.map((result, i) => {
            return <ResultComponent {...result} key={i} headingLevel={headingLevel} />;
        });
    } else if (searchTerm === undefined || searchTerm === "") {
        content = (
            <Paragraph className={classNames("searchResults-noResults", classes.noResults)}>{emptyMessage}</Paragraph>
        );
    } else {
        content = (
            <Paragraph className={classNames("searchResults-noResults", "isEmpty", classes.noResults)}>
                <Translate source="No results for '<0/>'." c0={searchTerm} />
            </Paragraph>
        );
    }

    const Tag = hasResults ? `ul` : `div`;

    return <Tag className={classNames("searchResults", classes.root, className)}>{content}</Tag>;
}
