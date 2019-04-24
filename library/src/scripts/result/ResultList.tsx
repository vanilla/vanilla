/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { searchResultsClasses } from "@library/features/search/searchResultsStyles";
import Translate from "@library/content/Translate";
import Result, { IResult } from "@library/result/Result";
import Paragraph from "@library/layout/Paragraph";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: any[];
    result?: React.ComponentClass;
    emptyMessage?: string;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default class ResultList extends React.Component<IProps> {
    public render() {
        const hasResults = this.props.results && this.props.results.length > 0;
        let content;
        const classes = searchBarClasses();
        const classesSearchResults = searchResultsClasses();

        if (hasResults) {
            const ResultComponent = this.props.result ? this.props.result : Result;
            content = this.props.results.map((result, i) => {
                return <ResultComponent {...result} key={i} />;
            });
        } else if (this.props.searchTerm === undefined || this.props.searchTerm === "") {
            content = (
                <Paragraph className={classNames("searchResults-noResults", classesSearchResults.noResults)}>
                    {this.props.emptyMessage ? this.props.emptyMessage : t("No results found.")}
                </Paragraph>
            );
        } else {
            content = (
                <Paragraph className={classNames("searchResults-noResults", "isEmpty", classesSearchResults.noResults)}>
                    <Translate source="No results for '<0/>'." c0={this.props.searchTerm} />
                </Paragraph>
            );
        }

        const Tag = hasResults ? `ul` : `div`;

        return (
            <Tag
                className={classNames(
                    "searchResults",
                    classesSearchResults.root,
                    this.props.className,
                    classes.results,
                )}
            >
                {content}
            </Tag>
        );
    }
}
