/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { RouteComponentProps, withRouter } from "react-router-dom";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import SearchBar from "@library/components/forms/select/SearchBar";
import { t } from "@library/application";
import SearchOption from "@library/components/search/SearchOption";
import { searchClasses } from "@library/styles/searchStyles";
import { buttonClasses, ButtonTypes } from "@library/styles/buttonStyles";

export interface ICompactSearchProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    buttonClass?: string;
    showingSuggestions?: boolean;
    onOpenSuggestions?: () => void;
    onCloseSuggestions?: () => void;
    buttonContentClass?: string;
    cancelContentClassName?: string;
    theme?: object;
}

interface IState {
    query: string;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
export class IndependentSearch extends React.Component<ICompactSearchProps, IState> {
    private id = uniqueIDFromPrefix("search");
    private resultsRef = React.createRef<HTMLDivElement>();

    public state: IState = {
        query: "",
    };

    public render() {
        const classes = searchClasses(this.props.theme);
        const buttons = buttonClasses(this.props.theme);
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
                    onOpenSuggestions={this.props.onOpenSuggestions}
                    onCloseSuggestions={this.props.onCloseSuggestions}
                    buttonClassName={buttons(ButtonTypes.TRANSPARENT)}
                />
                <div ref={this.resultsRef} className="search-results" />
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
}

export default withSearch(withRouter(IndependentSearch));
