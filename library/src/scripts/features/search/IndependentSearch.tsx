/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useCallback, useEffect } from "react";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SearchBar from "@library/features/search/SearchBar";
import { useUniqueID } from "@library/utility/idUtils";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/searchBarStyles";
import { RouteComponentProps, withRouter } from "react-router";
import classNames from "classnames";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import {
    useSearchScope,
    SEARCH_SCOPE_EVERYWHERE,
    SEARCH_SCOPE_LOCAL,
} from "@library/features/search/SearchScopeContext";
import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import merge from "lodash/merge";
import clone from "lodash/clone";

export interface IIndependentSearchProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    buttonClass?: string;
    buttonDropDownClass?: string;
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
    scope?: ISearchScopeNoCompact;
    initialQuery?: string;
    overwriteSearchBar?: ISearchBarOverwrites;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
export function IndependentSearch(props: IIndependentSearchProps) {
    const id = useUniqueID("search");
    const resultsRef = useRef<HTMLDivElement>(null);
    const [query, setQuery] = useState(props.initialQuery || "");
    const [forcedOptions, setForcedOptions] = useState<any[]>([]);
    const contextScope = useSearchScope();
    const scope = {
        ...contextScope,
        ...props.scope,
    };
    const hasScope = scope.optionsItems.length > 0;

    const { pushSmartLocation } = useLinkContext();

    const scopeValue = scope.value?.value || "";
    const handleSubmit = useCallback(() => {
        const searchQuery = [SEARCH_SCOPE_LOCAL, SEARCH_SCOPE_EVERYWHERE].includes(scopeValue)
            ? `${query}&scope=${scopeValue}`
            : query;
        pushSmartLocation(props.searchOptionProvider.makeSearchUrl(searchQuery));
    }, [props.searchOptionProvider, pushSmartLocation, query, scopeValue]);

    const handleSearchChange = useCallback(
        (newQuery: string) => {
            setQuery(newQuery);
        },
        [setQuery],
    );

    const { forceMenuOpen, searchOptionProvider } = props;
    useEffect(() => {
        if (forceMenuOpen) {
            searchOptionProvider.autocomplete("").then((results) => {
                setQuery("a");
                setForcedOptions(results);
            });
        }
    }, [forceMenuOpen, searchOptionProvider]);

    const classesSearchBar = searchBarClasses(
        merge(clone(props.overwriteSearchBar), { scope: hasScope ? scope : undefined }),
    );

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
                loadOptions={(query, options) =>
                    props.searchOptionProvider.autocomplete(query, { ...options, scope: scope.value?.value })
                }
                triggerSearchOnClear={false}
                resultsRef={resultsRef}
                buttonClassName={props.buttonClass}
                buttonDropDownClassName={props.buttonDropDownClass}
                buttonBaseClass={props.buttonBaseClass}
                buttonLoaderClassName={props.buttonLoaderClassName}
                hideSearchButton={props.hideSearchButton || hasScope}
                contentClass={props.contentClass}
                valueContainerClasses={props.valueContainerClasses}
                iconContainerClasses={props.iconContainerClasses}
                scope={props.scope}
                overwriteSearchBar={props.overwriteSearchBar}
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
