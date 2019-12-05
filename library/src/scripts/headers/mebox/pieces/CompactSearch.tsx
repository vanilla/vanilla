/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSearch } from "@library/contexts/SearchContext";
import SearchBar from "@library/features/search/SearchBar";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import SearchOption from "@library/features/search/SearchOption";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { compactSearchClasses } from "@library/headers/mebox/pieces/compactSearchStyles";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { SearchIcon } from "@library/icons/titleBar";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { useEscapeListener, useLastValue } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useLayoutEffect, useRef, useState } from "react";

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
}

/**
 * Implements Compact Search component for header
 */
export function CompactSearch(props: ICompactSearchProps) {
    const [query, setQuery] = useState("");
    const id = useUniqueID("compactSearch");
    const selfRef = useRef<HTMLDivElement | null>(null);
    const searchInputRef = useRef<SearchBar | null>(null);
    const resultsRef = useRef<HTMLDivElement | null>(null);
    const openSearchButtonRef = useRef<HTMLButtonElement | null>(null);

    const { focusOnMount, open } = props;
    const prevOpen = useLastValue(open);

    // Focus button on mount.
    useLayoutEffect(() => {
        if (focusOnMount) {
            searchInputRef.current?.focus();
        }
    }, [focusOnMount]);

    // Focus when opening/closing
    useLayoutEffect(() => {
        if (prevOpen === false && open === true) {
            searchInputRef.current?.focus();
        } else if (prevOpen === true && open === false) {
            openSearchButtonRef.current?.focus();
        }
    });

    const { searchOptionProvider } = useSearch();
    const { pushSmartLocation } = useLinkContext();

    const handleSubmit = () => {
        const searchUrl = searchOptionProvider.makeSearchUrl(query);
        pushSmartLocation(searchOptionProvider.makeSearchUrl(query));
    };

    // Close with the escape key.
    useEscapeListener({
        root: selfRef.current,
        callback: () => {
            if (!props.showingSuggestions) {
                props.onCloseSearch();
            }
        },
    });

    const classesTitleBar = titleBarClasses();
    const classes = compactSearchClasses();
    const classesSearchBar = searchBarClasses();
    const classesDropDown = dropDownClasses();
    return (
        <div
            ref={selfRef}
            className={classNames("compactSearch", props.className, classes.root, { isOpen: props.open })}
        >
            {!props.open && (
                <Button
                    onClick={props.onSearchButtonClick}
                    className={classNames(classesTitleBar.centeredButtonClass, props.buttonClass)}
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
                <div className={classNames("compactSearch-contents", classes.contents)}>
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
                            loadOptions={searchOptionProvider.autocomplete}
                            ref={searchInputRef}
                            triggerSearchOnClear={false}
                            resultsRef={resultsRef}
                            onOpenSuggestions={props.onOpenSuggestions}
                            onCloseSuggestions={props.onCloseSuggestions}
                            className={"compactSearch-searchBar"}
                            clearButtonClass={props.clearButtonClass}
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
                        className={classNames("compactSearch-close", props.cancelButtonClassName, classes.close)}
                        title={t("Search")}
                        aria-expanded={true}
                        aria-haspopup="true"
                        aria-controls={id}
                        baseClass={ButtonTypes.CUSTOM}
                    >
                        <div
                            className={classNames(
                                "compactSearch-cancelContents",
                                props.cancelContentClassName,
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

export default CompactSearch;
