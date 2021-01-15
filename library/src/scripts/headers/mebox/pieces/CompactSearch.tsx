/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/searchBarStyles";
import SearchOption from "@library/features/search/SearchOption";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { compactSearchClasses } from "@library/headers/mebox/pieces/compactSearchStyles";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { SearchIcon } from "@library/icons/titleBar";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { useEscapeListener, useLastValue, useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useLayoutEffect, useRef, useState, useCallback } from "react";
import { IStateColors } from "@library/styles/styleHelpers";
import {
    ISearchScopeNoCompact,
    useSearchScope,
    SEARCH_SCOPE_LOCAL,
    SEARCH_SCOPE_EVERYWHERE,
} from "@library/features/search/SearchScopeContext";
import { AsyncCreatable } from "react-select";
import { cx } from "@library/styles/styleShim";

export interface ICompactSearchProps {
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
    valueContainerClass?: string;
    scope?: ISearchScopeNoCompact;
    searchCloseOverwrites?: IStateColors;
    overwriteSearchBar?: ISearchBarOverwrites;
}

/**
 * Implements Compact Search component for header
 */
export function CompactSearch(props: ICompactSearchProps) {
    const [query, setQuery] = useState("");
    const id = useUniqueID("compactSearch");
    const selfRef = useRef<HTMLDivElement | null>(null);
    const searchInputRef = useRef<AsyncCreatable<any> | null>(null);
    const resultsRef = useRef<HTMLDivElement | null>(null);
    const openSearchButtonRef = useRef<HTMLButtonElement | null>(null);

    const { focusOnMount, open = false } = props;
    const prevOpen = useLastValue(open);

    const contextScope = useSearchScope();
    const scope = {
        ...contextScope,
        ...props.scope,
    };

    // Focus button on mount.
    useLayoutEffect(() => {
        if (focusOnMount) {
            searchInputRef.current?.focus();
        }
    }, [focusOnMount]);

    // Focus when opening/closing
    useLayoutEffect(() => {
        if (prevOpen === false && open) {
            searchInputRef.current?.focus();
        } else if (prevOpen === true && !open) {
            openSearchButtonRef.current?.focus();
        }
    });

    const { searchOptionProvider } = useSearch();
    const { pushSmartLocation } = useLinkContext();

    const scopeValue = scope.value?.value || "";
    const handleSubmit = useCallback(() => {
        const searchQuery = [SEARCH_SCOPE_LOCAL, SEARCH_SCOPE_EVERYWHERE].includes(scopeValue)
            ? `${query}&scope=${scopeValue}`
            : query;
        pushSmartLocation(searchOptionProvider.makeSearchUrl(searchQuery));
    }, [searchOptionProvider, pushSmartLocation, query, scopeValue]);

    // Close with the escape key.
    useEscapeListener({
        root: selfRef.current,
        callback: () => {
            if (!props.showingSuggestions) {
                props.onCloseSearch();
            }
        },
    });

    useFocusWatcher(selfRef, (isFocused) => {
        if (!isFocused) {
            props.onCloseSearch();
        }
    });

    const classesTitleBar = titleBarClasses();
    const classes = compactSearchClasses();
    const classesSearchBar = searchBarClasses(props.overwriteSearchBar);
    const classesDropDown = dropDownClasses();

    return (
        <div ref={selfRef} className={classNames(props.className, classes.root, { isOpen: props.open })}>
            {!props.open && (
                <Button
                    onClick={props.onSearchButtonClick}
                    className={classNames(classesTitleBar.centeredButton, props.buttonClass)}
                    title={t("Search")}
                    aria-expanded={false}
                    aria-haspopup="true"
                    baseClass={ButtonTypes.CUSTOM}
                    aria-controls={id}
                    buttonRef={openSearchButtonRef}
                >
                    <div className={classNames(props.buttonContentClassName)}>
                        <SearchIcon />
                    </div>
                </Button>
            )}
            {props.open && (
                <div className={classes.contents}>
                    <div className={classes.searchAndResults}>
                        <SearchBar
                            id={id}
                            placeholder={props.placeholder}
                            optionComponent={SearchOption}
                            noHeading={true}
                            title={t("Search")}
                            value={query}
                            disabled={!props.open}
                            hideSearchButton={true}
                            onChange={setQuery}
                            onSearch={handleSubmit}
                            loadOptions={(query, options) =>
                                searchOptionProvider.autocomplete(query, { ...options, scope: scope.value?.value })
                            }
                            ref={searchInputRef}
                            triggerSearchOnClear={false}
                            resultsRef={resultsRef}
                            onOpenSuggestions={props.onOpenSuggestions}
                            onCloseSuggestions={props.onCloseSuggestions}
                            className={"compactSearch-searchBar"}
                            clearButtonClass={props.clearButtonClass}
                            valueContainerClasses={classNames(classes.valueContainer, props.valueContainerClass)}
                            scope={props.scope}
                            overwriteSearchBar={props.overwriteSearchBar}
                        />

                        <div
                            ref={resultsRef}
                            className={classNames({
                                [classesTitleBar.compactSearchResults]: props.showingSuggestions,
                                [classesSearchBar.results]: props.showingSuggestions,
                                [classesDropDown.contents]: props.showingSuggestions,
                            })}
                        />
                    </div>

                    <Button
                        onClick={props.onCloseSearch}
                        className={cx(props.cancelButtonClassName, classes.close, classesSearchBar.closeButton)}
                        title={t("Cancel")}
                        aria-expanded={true}
                        aria-haspopup="true"
                        aria-controls={id}
                        baseClass={ButtonTypes.CUSTOM}
                    >
                        <div className={classNames(props.cancelContentClassName, classes.cancelContents)}>
                            {t("Cancel")}
                        </div>
                    </Button>
                </div>
            )}
        </div>
    );
}

export default CompactSearch;
