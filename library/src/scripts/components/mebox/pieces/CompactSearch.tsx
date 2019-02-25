/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import SearchBar from "@library/components/forms/select/SearchBar";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import { search } from "@library/components/icons/header";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import SearchOption from "@library/components/search/SearchOption";
import { withSearch, IWithSearchProps } from "@library/contexts/SearchContext";
import { withRouter, RouteComponentProps } from "react-router-dom";
import FocusWatcher from "@library/FocusWatcher";
import vanillaHeaderClasses from "@library/styles/vanillaHeaderStyles";
import { compactSearchClasses } from "@library/styles/compactSearchStyles";
import { searchBarClasses } from "@library/styles/searchBarStyles";

export interface ICompactSearchProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    open: boolean;
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
    clearButtonClass?: string;
}

interface IState {
    query: string;
}

/**
 * Implements Compact Search component for header
 */
export class CompactSearch extends React.Component<ICompactSearchProps, IState> {
    private id = uniqueIDFromPrefix("compactSearch");
    private openSearchButton = React.createRef<HTMLButtonElement>();
    private selfRef = React.createRef<HTMLDivElement>();
    private searchInputRef = React.createRef<SearchBar>();
    private resultsRef = React.createRef<HTMLDivElement>();
    public state: IState = {
        query: "",
    };

    public render() {
        const headerClasses = vanillaHeaderClasses();
        const classes = compactSearchClasses();
        const classesSearchBar = searchBarClasses();
        return (
            <div
                ref={this.selfRef}
                className={classNames("compactSearch", this.props.className, classes.root, { isOpen: this.props.open })}
            >
                {!this.props.open && (
                    <Button
                        onClick={this.props.onSearchButtonClick}
                        className={classNames(headerClasses.centeredButtonClass, this.props.buttonClass)}
                        title={t("Search")}
                        aria-expanded={false}
                        aria-haspopup="true"
                        baseClass={ButtonBaseClass.CUSTOM}
                        aria-controls={this.id}
                        buttonRef={this.openSearchButton}
                    >
                        <div className={classNames(this.props.buttonContentClass)}>{search()}</div>
                    </Button>
                )}
                {this.props.open && (
                    <div className={classNames("compactSearch-contents", classes.contents)}>
                        <SearchBar
                            id={this.id}
                            placeholder={this.props.placeholder}
                            optionComponent={SearchOption}
                            noHeading={true}
                            title={t("Search")}
                            value={this.state.query}
                            disabled={!this.props.open}
                            hideSearchButton={true}
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
                            clearButtonClass={this.props.clearButtonClass}
                        />
                        <Button
                            onClick={this.props.onCloseSearch}
                            className={classNames(
                                "compactSearch-close",
                                this.props.cancelButtonClassName,
                                classes.close,
                            )}
                            title={t("Search")}
                            aria-expanded={true}
                            aria-haspopup="true"
                            aria-controls={this.id}
                            baseClass={ButtonBaseClass.CUSTOM}
                        >
                            <div
                                className={classNames(
                                    "compactSearch-cancelContents",
                                    this.props.cancelContentClassName,
                                    classes.cancelContents,
                                )}
                            >
                                {t("Cancel")}
                            </div>
                        </Button>
                    </div>
                )}
                <div
                    ref={this.resultsRef}
                    className={classNames(
                        "vanillaHeader-compactSearchResults",
                        headerClasses.compactSearchResults,
                        classesSearchBar.results,
                    )}
                />
            </div>
        );
    }

    private focusWatcher: FocusWatcher;
    public componentDidMount() {
        this.focusWatcher = new FocusWatcher(this.selfRef.current!, this.handleFocusChange);
        this.focusWatcher.start();

        if (this.props.focusOnMount && this.props.open) {
            this.searchInputRef.current!.focus();
        }
    }

    public componentWillUnmount() {
        this.focusWatcher.stop();
    }

    private handleFocusChange = (gainedFocus: boolean) => {
        if (!gainedFocus && !this.selfRef.current!.contains(document.activeElement)) {
            this.props.onCloseSearch();
        }
    };

    private handleSearchChange = (newQuery: string) => {
        this.setState({ query: newQuery });
    };

    private handleSubmit = () => {
        const { searchOptionProvider, history } = this.props;
        const { query } = this.state;
        this.props.history.push(searchOptionProvider.makeSearchUrl(query));
    };

    public componentDidUpdate(prevProps) {
        if (!prevProps.open && this.props.open) {
            this.searchInputRef.current!.focus();
        } else if (prevProps.open && !this.props.open) {
            this.openSearchButton.current!.focus();
        }
    }

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

export default withSearch(withRouter(CompactSearch));
