/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "../dom/appUtils";
import Result, { IResult } from "./Result";
import Paragraph from "../layout/Paragraph";
import Translate from "../content/Translate";
import { searchBarClasses } from "../features/search/searchBarStyles";
import { searchResultClasses, searchResultsClasses } from "../features/search/searchResultsStyles";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: IResult[];
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
            content = this.props.results.map((result, i) => {
                return <Result {...result} key={i} />;
            });
        } else if (this.props.searchTerm === undefined || this.props.searchTerm === "") {
            content = (
                <Paragraph className={classNames("searchResults-noResults", classesSearchResults.noResults)}>
                    {t("No results found.")}
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
