/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useCallback, useEffect } from "react";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SearchBar from "@library/features/search/SearchBar";
import { useUniqueID } from "@library/utility/idUtils";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { RouteComponentProps, withRouter } from "react-router";
import classNames from "classnames";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";

interface IProps extends IWithSearchProps, RouteComponentProps<{}> {
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
    iconContainerClasses?: string;
    resultsAsModalClasses?: string;
    forceMenuOpen?: boolean;
}

interface IState {
    query: string;
    showingSuggestions: boolean;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
export function IndependentSearch(props: IProps) {
    const id = useUniqueID("search");
    const resultsRef = useRef<HTMLDivElement>(null);
    const [query, setQuery] = useState("");
    const [forcedOptions, setForcedOptions] = useState<any[]>([]);

    const { pushSmartLocation } = useLinkContext();

    const handleSubmit = useCallback(() => {
        pushSmartLocation(props.searchOptionProvider.makeSearchUrl(query));
    }, [props.searchOptionProvider, pushSmartLocation, query]);

    const handleSearchChange = useCallback(
        (newQuery: string) => {
            setQuery(newQuery);
        },
        [setQuery],
    );

    const { forceMenuOpen, searchOptionProvider } = props;
    useEffect(() => {
        if (forceMenuOpen) {
            searchOptionProvider.autocomplete("").then(results => {
                setQuery("a");
                setForcedOptions(results);
            });
        }
    }, [forceMenuOpen, searchOptionProvider]);

    const classesSearchBar = searchBarClasses();
    return (
        <div className={classNames(classesSearchBar.independentRoot, props.className)}>
            <SearchBar
                id={id}
                forceMenuOpen={props.forceMenuOpen}
                forcedOptions={forcedOptions}
                placeholder={props.placeholder}
                optionComponent={SearchOption}
                noHeading={true}
                title={t("Search")}
                value={query}
                onChange={handleSearchChange}
                onSearch={handleSubmit}
                loadOptions={props.searchOptionProvider.autocomplete}
                triggerSearchOnClear={false}
                resultsRef={resultsRef}
                buttonClassName={props.buttonClass}
                buttonBaseClass={props.buttonBaseClass}
                buttonLoaderClassName={props.buttonLoaderClassName}
                hideSearchButton={props.hideSearchButton}
                contentClass={props.contentClass}
                valueContainerClasses={props.valueContainerClasses}
                iconContainerClasses={props.iconContainerClasses}
            />
            <div
                ref={resultsRef}
                className={classNames("search-results", {
                    [classesSearchBar.results]: !!query,
                    [classesSearchBar.resultsAsModal]: !!query,
                    [props.resultsAsModalClasses ?? ""]: !!query,
                })}
            />
        </div>
    );
}

export default withSearch(withRouter(IndependentSearch));
