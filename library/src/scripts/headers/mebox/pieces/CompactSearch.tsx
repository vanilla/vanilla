/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { compactSearchClasses } from "@library/headers/mebox/pieces/compactSearchStyles";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { RouteComponentProps, withRouter } from "react-router";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { SearchIcon } from "@library/icons/titleBar";

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
    buttonContentClassName?: string;
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
        const classesTitleBar = titleBarClasses();
        const classes = compactSearchClasses();
        const classesSearchBar = searchBarClasses();
        const classesDropDown = dropDownClasses();
        return (
            <div
                ref={this.selfRef}
                className={classNames("compactSearch", this.props.className, classes.root, { isOpen: this.props.open })}
            >
                {!this.props.open && (
                    <Button
                        onClick={this.props.onSearchButtonClick}
                        className={classNames(classesTitleBar.centeredButtonClass, this.props.buttonClass)}
                        title={t("Search")}
                        aria-expanded={false}
                        aria-haspopup="true"
                        baseClass={ButtonTypes.CUSTOM}
                        aria-controls={this.id}
                        buttonRef={this.openSearchButton}
                    >
                        <div className={classNames(this.props.buttonContentClassName)}>
                            <SearchIcon />
                        </div>
                    </Button>
                )}
                {this.props.open && (
                    <div className={classNames("compactSearch-contents", classes.contents)}>
                        <div className={classes.searchAndResults}>
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

                            <div
                                ref={this.resultsRef}
                                className={classNames({
                                    [classesTitleBar.compactSearchResults]: this.props.showingSuggestions,
                                    [classesSearchBar.results]: this.props.showingSuggestions,
                                    [classesDropDown.contents]: this.props.showingSuggestions,
                                })}
                            />
                        </div>

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
                            baseClass={ButtonTypes.CUSTOM}
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
            </div>
        );
    }

    public componentDidMount() {
        if (this.props.focusOnMount && this.props.open) {
            this.searchInputRef.current!.focus();
        }
    }

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
