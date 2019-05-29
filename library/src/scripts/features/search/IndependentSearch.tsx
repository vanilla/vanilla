/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SearchBar from "@library/features/search/SearchBar";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { searchClasses } from "@library/features/search/searchStyles";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { RouteComponentProps, withRouter } from "react-router";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpers";

export interface ICompactSearchProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    buttonClass?: string;
    inputClass?: string;
    iconClass?: string;
    buttonContentClassName?: string;
    buttonLoaderClassName?: string;
    cancelContentClassName?: string;
    contentClass?: string;
    valueContainerClasses?: string;
    hideSearchButton?: boolean;
    isLarge?: boolean;
    buttonBaseClass?: ButtonTypes;
}

interface IState {
    query: string;
    showingSuggestions: boolean;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
export class IndependentSearch extends React.Component<ICompactSearchProps, IState> {
    private id = uniqueIDFromPrefix("search");
    private resultsRef = React.createRef<HTMLDivElement>();

    public state: IState = {
        query: "",
        showingSuggestions: false,
    };

    public render() {
        const classes = searchClasses();
        const classesSearchBar = searchBarClasses();
        return (
            <div className={classNames(classes.root, this.props.className)}>
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
                    triggerSearchOnClear={false}
                    resultsRef={this.resultsRef}
                    onOpenSuggestions={this.setOpenSuggestions}
                    onCloseSuggestions={this.setCloseSuggestions}
                    buttonClassName={this.props.buttonClass}
                    buttonBaseClass={this.props.buttonBaseClass}
                    className={classes.root}
                    isBigInput={this.props.isLarge}
                    buttonLoaderClassName={this.props.buttonLoaderClassName}
                    hideSearchButton={this.props.hideSearchButton}
                    contentClass={this.props.contentClass}
                    valueContainerClasses={this.props.valueContainerClasses}
                />
                <div
                    ref={this.resultsRef}
                    className={classNames("search-results", {
                        [classesSearchBar.results]: this.state.showingSuggestions,
                        [classesSearchBar.resultsAsModal]: this.state.showingSuggestions,
                        [visibility().displayNone]: !this.state.showingSuggestions,
                    })}
                />
            </div>
        );
    }

    /**
     * Keep track of visibility of suggestions
     */
    public setOpenSuggestions = () => {
        this.setState({
            showingSuggestions: true,
        });
    };

    /**
     * Keep track of visibility of suggestions
     */
    public setCloseSuggestions = () => {
        this.setState({
            showingSuggestions: false,
        });
    };

    private handleSubmit = () => {
        const { searchOptionProvider, history } = this.props;
        const { query } = this.state;
        this.props.history.push(searchOptionProvider.makeSearchUrl(query));
    };

    private handleSearchChange = (newQuery: string) => {
        this.setState({ query: newQuery });
    };
}

export default withSearch(withRouter(IndependentSearch));
