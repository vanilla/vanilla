/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Translate from "@library/content/Translate";
import { IComboBoxOption, ISearchBarProps } from "@library/features/search/ISearchBarProps";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/SearchBar.styles";
import { searchBarVariables } from "@library/features/search/SearchBar.variables";
import { SearchScope } from "@library/features/search/SearchScope";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ClearButton } from "@library/forms/select/ClearButton";
import * as selectOverrides from "@library/forms/select/overwrites";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import Heading from "@library/layout/Heading";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { t } from "@library/utility/appUtils";
import { useUniqueID, uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useEffect, useRef, useState, useCallback, useMemo } from "react";
import ReactDOM from "react-dom";
import { AsyncCreatable, components } from "react-select";
import { MenuProps } from "react-select/lib/components/Menu";
import { ActionMeta, InputActionMeta } from "react-select/lib/types";
import { useSearchScope } from "@library/features/search/SearchScopeContext";
import { useSearchSources } from "@library/search/SearchSourcesContext";
import { PLACES_CATEGORY_TYPE, PLACES_KNOWLEDGE_BASE_TYPE, PLACES_GROUP_TYPE } from "@library/search/searchConstants";
import { Icon } from "@vanilla/icons";
import { cx } from "@emotion/css";
import { sprintf } from "sprintf-js";

// Re-exported after being moved.
export type { IComboBoxOption };

export default React.forwardRef(function SearchBar(
    props: ISearchBarProps,
    incomingRef: React.MutableRefObject<AsyncCreatable<any>>,
) {
    const { pushSmartLocation } = useLinkContext();
    props = {
        disabled: false,
        noHeading: false,
        isLoading: false,
        optionComponent: selectOverrides.SelectOption,
        triggerSearchOnClear: false,
        disableAutocomplete: false,
        placeholder: "",
        ...props,
    };
    const { sources } = useSearchSources();
    const [isFocused, setFocused] = useState(false);
    const prefix = "searchBar";
    const ownID = useUniqueID(prefix);
    const id = props.id ?? ownID;
    const searchButtonID = id + "-searchButton";
    const searchInputID = id + "-searchInput";
    const ownRef = useRef<AsyncCreatable<any> | null>(null);
    const inputRef = incomingRef ?? ownRef;

    const [forceMenuClosed, setForceMenuClosed] = useState(false);
    const { onSearch, onChange, disableAutocomplete, optionComponent, resultsRef } = props;

    /**
     * Handle changes in the select's text input.
     *
     * Ignores change caused by blurring or closing the menu. These normally clear the input.
     */
    const handleInputChange = useCallback(
        (value: string, reason: InputActionMeta) => {
            if (!["input-blur", "menu-close"].includes(reason.action)) {
                onChange(value);
                setForceMenuClosed(false);
            }
        },
        [onChange, setForceMenuClosed],
    );

    /**
     * Handle changes in option.
     *
     * - Update the input value.
     * - Force the menu closed.
     * - Trigger a search.
     */
    const handleOptionChange = useCallback(
        (option: IComboBoxOption, actionMeta: ActionMeta) => {
            if (option) {
                if (disableAutocomplete) {
                    onChange(option.label);
                    onSearch();
                } else {
                    const data = option.data || {};
                    const { url } = data;

                    if (actionMeta.action === "select-option" && url) {
                        pushSmartLocation(url);
                    } else if (actionMeta.action === "create-option") {
                        onSearch();
                    } else {
                        onChange(option.label);
                        if (disableAutocomplete) {
                            onSearch();
                        } else {
                            setForceMenuClosed(true);
                        }
                    }
                }
            }
        },
        [disableAutocomplete, onChange, onSearch, setForceMenuClosed, pushSmartLocation],
    );

    /**
     * Determine if we should show the menu or not.
     *
     * - Menu can be forced closed through state.
     * - Having no value in the input keeps the search closed.
     * - Otherwise falls back to what is determined by react-select.
     */
    const isMenuVisible: boolean | undefined =
        forceMenuClosed || props.value.length === 0 || props.disableAutocomplete ? false : undefined;

    const classes = searchBarClasses(props.overwriteSearchBar);

    // Stash the props in a a ref so the inner components can use the latest value, but still have a stable identity.
    const controlProps = {
        ...props,
        isFocused,
        focusInput: () => {
            inputRef.current?.focus();
        },
        searchButtonID,
        searchInputID,
    };
    const controlPropsRef = useRef<IControlProps>(controlProps);
    useEffect(() => {
        controlPropsRef.current = controlProps;
        inputRef.current?.forceUpdate();
    });

    // Make sure these are have a stable identity.
    const components = useMemo(() => {
        return {
            // Use our custom searchbar UI.
            Control: function ControlWrapper(innerProps: any) {
                return <SearchBarControl {...innerProps} {...controlPropsRef.current} />;
            },
            IndicatorSeparator: selectOverrides.NullComponent,

            // If we are provided with a custom spot to mount the menu, mount it there instead.
            Menu: resultsRef
                ? function MenuWrapper(menuProps: any) {
                      return <SearchBarMenuPortal {...menuProps} mountRef={resultsRef!} />;
                  }
                : selectOverrides.Menu,
            MenuList: selectOverrides.MenuList,
            Option: optionComponent!,
            NoOptionsMessage: selectOverrides.NoOptionsMessage,
            ClearIndicator: selectOverrides.NullComponent,
            DropdownIndicator: selectOverrides.NullComponent,
            LoadingMessage: selectOverrides.OptionLoader,
            Group: selectOverrides.Group,
        };
    }, [optionComponent, resultsRef]);

    const [options, setOptions] = useState<any[]>([]);

    const menuIsOpen = (props.resultsRef?.current && props.forceMenuOpen) || isMenuVisible;

    return (
        <AsyncCreatable
            id={id}
            value={undefined}
            onChange={handleOptionChange}
            closeMenuOnSelect={false}
            inputId={searchInputID}
            inputValue={props.value}
            onInputChange={handleInputChange}
            components={components}
            isClearable={false}
            blurInputOnSelect={false}
            allowCreateWhileLoading={true}
            controlShouldRenderValue={false}
            isDisabled={props.disabled}
            isValidNewOption={() => true}
            cached={true}
            loadOptions={() => {
                return props.loadOptions?.(props.value).then((loadedOptions) => {
                    // We want items belonging to group, category, and kb types to be on top
                    // of the list (inline elements), but we cannot simply copy over, since
                    // react-select use label to identify the items, and so there will be
                    // double hover, double listing etc. behavior.
                    const placesListingResults = loadedOptions
                        .filter((option) =>
                            [PLACES_GROUP_TYPE, PLACES_CATEGORY_TYPE, PLACES_KNOWLEDGE_BASE_TYPE].includes(option.type),
                        )
                        .map((option) => ({ ...option, label: `places___${option.label}___` }));

                    if (placesListingResults[0]) {
                        placesListingResults[0].data.isFirst = true;
                    }

                    const results = [...placesListingResults, ...loadedOptions];

                    setOptions(results);

                    //first we'll load results with no source specified (e.g. kb, which is still community results) or with "community" source, then results from custom connector
                    return [
                        ...results.filter(({ source }) => !source || source === "community"),
                        ...sources
                            .filter((source) => source.key !== "community")
                            .map((source) => ({
                                label: source.label,
                                options: results.filter(
                                    ({ source: resultSourceKey }) => source.key === resultSourceKey,
                                ),
                            })),
                    ];
                });
            }}
            defaultOptions={props.forcedOptions}
            menuIsOpen={menuIsOpen}
            classNamePrefix={prefix}
            className={classNames(classes.wrap, props.className)}
            placeholder={props.placeholder ?? t("Search")}
            aria-label={sprintf(t("Search %s"), t("Text"))}
            escapeClearsValue={true}
            pageSize={20}
            theme={getReactSelectTheme}
            styles={reactSelectCustomStyles}
            backspaceRemovesValue={true}
            createOptionPosition="first"
            formatCreateLabel={formatReactSelectLabel}
            ref={inputRef}
            onKeyDown={
                props.handleOnKeyDown ??
                ((event: React.KeyboardEvent<HTMLInputElement>) => {
                    const triggeringElement = event.target as HTMLInputElement;

                    if (
                        triggeringElement.value &&
                        event.key === "Tab" &&
                        !event.shiftKey &&
                        triggeringElement.tagName !== "BUTTON"
                    ) {
                        event.preventDefault();
                        const clearButton = triggeringElement.closest(".isClearable")?.querySelector("button");
                        if (clearButton) {
                            clearButton.focus();
                        }
                    }

                    if (!isFocused) {
                        return;
                    }

                    if (!(triggeringElement instanceof HTMLInputElement)) {
                        return;
                    }

                    if (event.key === "Enter") {
                        return true; // submits form
                    } else if (event.key === "Home") {
                        if (options.length === 0) {
                            event.preventDefault();
                            triggeringElement.setSelectionRange(0, 0);
                            setForceMenuClosed(true);
                            return;
                        }
                    } else if (event.key === "End") {
                        if (options.length === 0) {
                            event.preventDefault();
                            const length = triggeringElement.value.length;
                            triggeringElement.setSelectionRange(length, length);
                            return;
                        }
                    }
                    setForceMenuClosed(false);
                    return;
                })
            }
            onMenuOpen={props.onOpenSuggestions}
            onMenuClose={props.onCloseSuggestions}
            onFocus={() => setFocused(true)}
            onBlur={() => setFocused(false)}
            iconContainerClasses={props.iconContainerClasses}
            resultsAsModalClasses={props.resultsAsModalClasses}
        />
    );
});

function getReactSelectTheme(theme: any) {
    return {
        ...theme,
        borderRadius: {},
        colors: {},
        spacing: {},
    };
}

const reactSelectCustomStyles = {
    option: (provided: React.CSSProperties) => ({
        ...provided,
    }),
    menu: (provided: React.CSSProperties) => {
        return { ...provided, backgroundColor: undefined, boxShadow: undefined };
    },
    menuList: (provided: React.CSSProperties) => {
        return { ...provided, maxHeight: undefined };
    },
    control: (provided: React.CSSProperties) => ({
        ...provided,
        borderWidth: 0,
    }),
};

/**
 * Create a label for React Select's "Add option" option.
 */
const formatReactSelectLabel = (inputValue: string) => {
    return (
        <span className="suggestedTextInput-searchingFor">
            <Translate source="Search for <0/>" c0={<strong>{inputValue}</strong>} />
        </span>
    );
};

/**
 * Portal wrapper around the search bar menu.
 */
function SearchBarMenuPortal(
    props: MenuProps<any> & { mountRef: React.RefObject<HTMLDivElement>; isDisabled?: boolean },
    overwriteSearchBar: ISearchBarOverwrites,
) {
    const classes = searchBarClasses(overwriteSearchBar);
    const { mountRef } = props;
    if (mountRef.current == null) {
        return null;
    }

    return ReactDOM.createPortal(
        <components.Menu
            {...props}
            className={classNames(
                "suggestedTextInput-menu",
                "dropDown-contents",
                "isParentWidth",
                classes.menu,
                classes.results,
            )}
        />,
        mountRef.current,
    );
}

interface IControlProps extends ISearchBarProps {
    isFocused: boolean;
    focusInput: () => void;
    searchButtonID: string;
    searchInputID: string;
}

/**
 * Control component for inside of react select (primary visual searchbox).
 */
function SearchBarControl(props: IControlProps) {
    const contextScope = useSearchScope();
    const searchBarVars = searchBarVariables();
    const scope = {
        ...contextScope,
        ...props.scope,
    };
    const { hideSearchButton, overwriteSearchBar, isFocused, focusInput, searchButtonID, searchInputID } = props;
    const { optionsItems = [], value, onChange } = scope;
    const hasScope = scope.optionsItems.length > 1;
    const searchButtonIsVisible = !hasScope ? !hideSearchButton : false;
    const {
        compact = false,
        borderRadius = searchBarVars.border.radius,
        preset = searchBarVars.options.preset,
    } = overwriteSearchBar || {};

    // In case we'll need to use ID
    const ID = useMemo(() => uniqueIDFromPrefix("search"), []);
    const buttonID = ID + "-button";

    const classes = searchBarClasses({
        borderRadius,
        compact,
        preset,
    });
    const [isHovered, setHovered] = useState(false);

    // Use this state to record if the clear button has been clicked
    const [shouldSearch, setShouldSearch] = useState(false);

    // Destructure some props for convenience
    const { value: propsValue, triggerSearchOnClear, onSearch } = props;

    // This effect will actually perform the search and "reset" the state
    useEffect(() => {
        if (shouldSearch && propsValue?.length < 1) {
            onSearch();
            setShouldSearch(false);
        }
    }, [onSearch, triggerSearchOnClear, propsValue]);

    const clearInput = (event: React.SyntheticEvent) => {
        event.preventDefault();
        props.onChange("");
        // If the flag is set, we indicate that a search should occur when the value is next updated
        triggerSearchOnClear && setShouldSearch(true);
        focusInput();
    };

    /**
     * Handle the form submission.
     */
    const onFormSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        props.onSearch();
    };

    return (
        <form role="search" className={classNames(classes.form, classes.root)} onSubmit={onFormSubmit}>
            <div
                onClick={focusInput}
                className={classNames(classes.content, props.contentClass, {
                    hasFocus: isFocused,
                    withoutScope: !hasScope,
                    withScope: hasScope,
                    withButton: searchButtonIsVisible || hasScope,
                    withoutButton: !searchButtonIsVisible && !hasScope,
                })}
            >
                {!compact && !hasScope && (
                    <Button
                        buttonType={ButtonTypes.CUSTOM}
                        onClick={() => {
                            focusInput();
                            props.onSearch();
                        }}
                        className={classes.iconContainer(hasScope)}
                        ariaLabel={t("Search")}
                        title={t("Search")}
                        tabIndex={1}
                    >
                        <Icon size="compact" icon="search-search" />
                    </Button>
                )}
                {hasScope && (
                    <SearchScope
                        selectBoxProps={{ options: optionsItems, value: value, onChange: onChange }}
                        compact={compact}
                        overwriteSearchBar={overwriteSearchBar}
                        separator={!isHovered ? <div className={classes.scopeSeparator} role="presentation" /> : null}
                    />
                )}
                <div
                    onMouseEnter={() => setHovered(true)}
                    onMouseLeave={() => setHovered(false)}
                    className={classNames(classes.main, {
                        ["focus-visible"]: isFocused, // Note that the plugin we use for keyboard focus can't tell the difference between a mouse and a keyboard focus for text inputs
                        isFocused,
                        isHovered,
                        withoutScope: !hasScope,
                        withScope: hasScope,
                        withButton: searchButtonIsVisible,
                        withoutButton: !searchButtonIsVisible,
                    })}
                >
                    <div
                        className={classNames(
                            "suggestedTextInput-inputText",
                            "inputText",
                            "isClearable",
                            "searchBar-valueContainer", // intentionally hard coded class name
                            classes.valueContainer,
                            props.valueContainerClasses,
                            {
                                ["focus-visible"]: isFocused, // Note that the plugin we use for keyboard focus can't tell the difference between a mouse and a keyboard focus for text inputs
                                isFocused,
                                isHovered,
                                [classes.compoundValueContainer]: searchButtonIsVisible,
                                withoutScope: !hasScope,
                                withScope: hasScope,
                                withButton: searchButtonIsVisible || hasScope,
                                withoutButton: !searchButtonIsVisible && !hasScope,
                                compactScope: hasScope && compact,
                            },
                        )}
                    >
                        <components.Control {...(props as any)} />
                        {props.value && (
                            <ClearButton
                                onClick={clearInput}
                                className={classNames(
                                    classes.clear,
                                    { [classes.clearWithButtonWithoutScope]: searchButtonIsVisible && !hasScope },
                                    props.clearButtonClass,
                                )}
                            />
                        )}
                    </div>
                </div>
            </div>

            <ConditionalWrap condition={!hasScope} className={visibility().visuallyHidden}>
                {hasScope && (
                    <Button
                        buttonType={ButtonTypes.CUSTOM}
                        onClick={() => {
                            focusInput();
                            props.onSearch();
                        }}
                        className={classes.hasScopeIconContainer}
                        ariaLabel={t("Search")}
                        title={t("Search")}
                        tabIndex={0}
                    >
                        <Icon size="compact" icon="search-search" />
                    </Button>
                )}
            </ConditionalWrap>

            <ConditionalWrap condition={!searchButtonIsVisible} className={visibility().visuallyHidden}>
                {searchButtonIsVisible && (
                    <Button
                        submit={true}
                        id={searchButtonID}
                        buttonType={props.buttonType}
                        className={cx(classes.submitButton, props.buttonClassName ?? classes.actionButton)}
                        tabIndex={0}
                    >
                        {props.isLoading ? (
                            <ButtonLoader className={props.buttonLoaderClassName} buttonType={props.buttonType} />
                        ) : (
                            props.buttonText || t("Search")
                        )}
                    </Button>
                )}
            </ConditionalWrap>
        </form>
    );
}
