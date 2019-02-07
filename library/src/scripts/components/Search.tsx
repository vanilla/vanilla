/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { withRouter, RouteComponentProps } from "react-router-dom";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import SearchBar from "@library/components/forms/select/SearchBar";
import { t } from "@library/application";
import SearchOption from "@library/components/search/SearchOption";
import { searchClasses } from "@library/styles/searchStyles";

export interface ICompactSearchProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    onSearchButtonClick: () => void;
    onCloseSearch: () => void;
    cancelButtonClassName?: string;
    buttonClass?: string;
    showingSuggestions?: boolean;
    onOpenSuggestions?: () => void;
    onCloseSuggestions?: () => void;
    focusOnMount?: boolean;
    buttonContentClass?: string;
    cancelContentClassName?: string;
}

interface IState {
    query: string;
}

/**
 * Implements Compact Search component for header
 */
export class Search extends React.Component<ICompactSearchProps, IState> {
    private id = uniqueIDFromPrefix("search");
    private selfRef = React.createRef<HTMLDivElement>();
    private searchInputRef = React.createRef<SearchBar>();
    private resultsRef = React.createRef<HTMLDivElement>();

    public state: IState = {
        query: "",
    };

    public render() {
        const classes = searchClasses();
        return (
            <div ref={this.selfRef} className={classNames(classes.root, this.props.className)}>
                <>
                    <div className={classNames("compactSearch-contents")}>
                        <SearchBar
                            id={this.id}
                            placeholder={this.props.placeholder}
                            optionComponent={SearchOption}
                            noHeading={true}
                            title={t("Search")}
                            value={this.state.query}
                            onChange={this.handleSearchChange}
                            onSearch={this.handleSubmit}
                            loadOptions={this.props.searchOptionProvider.autocomplete}
                            ref={this.searchInputRef}
                            triggerSearchOnClear={false}
                            resultsRef={this.resultsRef}
                            handleOnKeyDown={this.handleKeyDown}
                            onOpenSuggestions={this.props.onOpenSuggestions}
                            onCloseSuggestions={this.props.onCloseSuggestions}
                            className={"compactSearch-searchBar"}
                        />
                    </div>
                </>
                <div
                    ref={this.resultsRef}
                    className={classNames("vanillaHeader-compactSearchResults", classes.results)}
                />
            </div>
        );
    }

    private handleSearchChange = (newQuery: string) => {
        this.setState({ query: newQuery });
    };

    private handleSubmit = () => {
        const { searchOptionProvider, history } = this.props;
        const { query } = this.state;
        this.props.history.push(searchOptionProvider.makeSearchUrl(query));
    };

    /**
     * Keyboard handler
     * @param event
     */
    private handleKeyDown = (event: React.KeyboardEvent) => {
        if (!this.props.showingSuggestions) {
            switch (event.key) {
                case "Escape":
                    this.props.onCloseSearch();
                    break;
            }
        }
    };
}

export default withSearch(withRouter(Search));
