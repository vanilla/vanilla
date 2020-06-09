/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { searchResultsClasses } from "@library/features/search/searchResultsStyles";
import Translate from "@library/content/Translate";
import Result from "@library/result/Result";
import Paragraph from "@library/layout/Paragraph";

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
export default class ResultList extends React.Component<IProps> {
    public render() {
        const hasResults = this.props.results && this.props.results.length > 0;
        let content;
        const classes = searchResultsClasses();

        if (hasResults) {
            const ResultComponent = this.props.result ? this.props.result : Result;
            content = this.props.results.map((result, i) => {
                return <ResultComponent {...result} key={i} headingLevel={this.props.headingLevel} />;
            });
        } else if (this.props.searchTerm === undefined || this.props.searchTerm === "") {
            content = (
                <Paragraph className={classNames("searchResults-noResults", classes.noResults)}>
                    {this.props.emptyMessage ? this.props.emptyMessage : t("No results found.")}
                </Paragraph>
            );
        } else {
            content = (
                <Paragraph className={classNames("searchResults-noResults", "isEmpty", classes.noResults)}>
                    <Translate source="No results for '<0/>'." c0={this.props.searchTerm} />
                </Paragraph>
            );
        }

        const Tag = hasResults ? `ul` : `div`;

        return <Tag className={classNames("searchResults", classes.root, this.props.className)}>{content}</Tag>;
    }
}
