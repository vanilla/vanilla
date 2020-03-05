/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState } from "react";

interface IQueryValues {
    [key: string]: any;
}

interface ISearchFilterContextValue {
    /**
     * Get all of the DOM elements for selecting query values for a search domain.
     * @param domain The search domain to get values for.
     */
    getFilterComponentsForDomain(domain: string): React.ReactNode;

    /**
     * Get all of the parameters to filter a search for.
     * @param domain The search domain to get parameters for.
     */
    getQueryValuesForDomain(domain: string): IQueryValues;

    /**
     * Get all of the parameters to filter a search for.
     * @param domain The search domain to get parameters for.
     */
    updateQueryValuesForDomain(domain: string, values: IQueryValues): void;
}

const EMPTY_QUERY_VALUES = {};

const SearchFilterContext = React.createContext<ISearchFilterContextValue>({
    getFilterComponentsForDomain: () => null,
    getQueryValuesForDomain: () => EMPTY_QUERY_VALUES,
    updateQueryValuesForDomain: () => {},
});

interface IProps {
    children?: React.ReactNode;
}

export function SearchFilterContextProvider(props: IProps) {
    let [queryValuesByDomain, setQueryValuesByDomain] = useState<{ [domain: string]: IQueryValues }>({});

    return (
        <SearchFilterContext.Provider
            value={{
                getFilterComponentsForDomain: (domain: string) => {
                    return SearchFilterContextProvider.extraFilters.map((extraFilter, i) => {
                        if (extraFilter.searchDomain === domain) {
                            return <React.Fragment key={i}>{extraFilter.filterNode}</React.Fragment>;
                        } else {
                            return null;
                        }
                    });
                },
                getQueryValuesForDomain: (domain: string) => {
                    const existingValues = queryValuesByDomain[domain] || EMPTY_QUERY_VALUES;
                    return existingValues;
                },
                updateQueryValuesForDomain: (domain: string, newValues: IQueryValues) => {
                    const existingValues = queryValuesByDomain[domain] || EMPTY_QUERY_VALUES;
                    setQueryValuesByDomain({
                        ...queryValuesByDomain,
                        [domain]: {
                            ...existingValues,
                            ...newValues,
                        },
                    });
                },
            }}
        >
            {props.children}
        </SearchFilterContext.Provider>
    );
}

export function useSearchFilters() {
    return useContext(SearchFilterContext);
}

interface IExtraFilter {
    searchDomain: string;
    filterNode: React.ReactNode;
}

SearchFilterContextProvider.extraFilters = [] as IExtraFilter[];

SearchFilterContextProvider.addSearchFilter = (domain: string, filterNode: React.ReactNode) => {
    SearchFilterContextProvider.extraFilters.push({
        searchDomain: domain,
        filterNode,
    });
};
