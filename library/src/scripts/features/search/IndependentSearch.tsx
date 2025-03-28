/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useCallback, useEffect } from "react";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import { IWithSearchProps, useSearch, withSearch } from "@library/contexts/SearchContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SearchBar from "@library/features/search/SearchBar";
import { useUniqueID } from "@library/utility/idUtils";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/SearchBar.styles";
import { RouteComponentProps, withRouter } from "react-router";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import {
    useSearchScope,
    SEARCH_SCOPE_EVERYWHERE,
    SEARCH_SCOPE_LOCAL,
} from "@library/features/search/SearchScopeContext";
import { ISearchScopeNoCompact } from "@library/features/search/SearchScopeContext";
import merge from "lodash-es/merge";
import clone from "lodash-es/clone";
import { cx } from "@emotion/css";

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
    buttonType?: ButtonTypes;
    iconContainerClasses?: string;
    resultsAsModalClasses?: string;
    forceMenuOpen?: boolean;
    scope?: ISearchScopeNoCompact;
    initialQuery?: string;
    initialParams?: Record<string, any>;
    overwriteSearchBar?: ISearchBarOverwrites;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
function IndependentSearch(props: IIndependentSearchProps) {
    const id = useUniqueID("search");
    const resultsRef = useRef<HTMLDivElement>(null);
    const { initialParams } = props;
    const [query, setQuery] = useState(props.initialQuery || "");
    const [forcedOptions, setForcedOptions] = useState<any[]>([]);
    const contextScope = useSearchScope();
    const scope = {
        ...contextScope,
        ...props.scope,
    };
    const hasScope = scope.optionsItems.length > 0;

    const { pushSmartLocation } = useLinkContext();
    const { externalSearch } = useSearch();

    const scopeValue = scope.value?.value || "";
    const handleSubmit = useCallback(() => {
        const queryParams: Record<string, any> = {
            ...initialParams,
        };

        if ([SEARCH_SCOPE_LOCAL, SEARCH_SCOPE_EVERYWHERE].includes(scopeValue)) {
            queryParams.scope = scopeValue;
        }

        const searchUrl = searchOptionProvider.makeSearchUrl(query, queryParams, externalSearch?.query);

        if (externalSearch?.query && externalSearch?.resultsInNewTab) {
            window.open(searchUrl, "_blank");
            return;
        }

        pushSmartLocation(searchUrl);
    }, [props.searchOptionProvider, pushSmartLocation, query, scopeValue, externalSearch]);

    const handleSearchChange = useCallback(
        (newQuery: string) => {
            setQuery(newQuery);
        },
        [setQuery],
    );

    const { forceMenuOpen, searchOptionProvider } = props;
    useEffect(() => {
        if (forceMenuOpen) {
            void searchOptionProvider.autocomplete("").then((results) => {
                setQuery("a");
                setForcedOptions(results);
            });
        }
    }, [forceMenuOpen, searchOptionProvider]);

    const classesSearchBar = searchBarClasses(
        merge(clone(props.overwriteSearchBar), { scope: hasScope ? scope : undefined }),
    );

    return (
        <div className={cx(classesSearchBar.independentRoot, props.className)}>
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
                    externalSearch?.query
                        ? Promise.resolve([])
                        : props.searchOptionProvider.autocomplete(query, {
                              ...initialParams,
                              ...options,
                              scope: scope.value?.value,
                          })
                }
                triggerSearchOnClear={false}
                resultsRef={resultsRef}
                buttonClassName={props.buttonClass}
                buttonType={props.buttonType}
                buttonLoaderClassName={props.buttonLoaderClassName}
                hideSearchButton={props.hideSearchButton || hasScope}
                contentClass={props.contentClass}
                valueContainerClasses={props.valueContainerClasses}
                iconContainerClasses={props.iconContainerClasses}
                scope={props.scope}
                overwriteSearchBar={props.overwriteSearchBar}
                disableAutocomplete={!!externalSearch?.query}
            />
            <div
                ref={resultsRef}
                className={cx("search-results", {
                    [classesSearchBar.results]: !!query,
                    [classesSearchBar.resultsAsModal]: !!query,
                    [props.resultsAsModalClasses ?? ""]: !!query,
                })}
            />
        </div>
    );
}

export default withSearch(withRouter(IndependentSearch));
