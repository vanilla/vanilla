import { DEFAULT_CORE_SEARCH_FORM, INITIAL_SEARCH_STATE } from "@library/search/searchReducer";
import { ISearchForm, ISearchResponse } from "@library/search/searchTypes";
import { ILoadable } from "@library/@types/api/core";
import React, { useContext } from "react";
import { ISearchDomain } from "./SearchService";

export const SearchContext = React.createContext<ISearchContextValue>({
    getFilterComponentsForDomain: () => null,
    updateForm: () => {},
    resetForm: () => {},
    results: INITIAL_SEARCH_STATE.results,
    domainSearchResponse: INITIAL_SEARCH_STATE.domainSearchResponse,
    form: DEFAULT_CORE_SEARCH_FORM,
    search: () => {},
    searchInDomain: () => {},
    getDomains: () => {
        return [];
    },
    getCurrentDomain: () => {
        throw new Error("Context implementation is required for this method");
    },
    getDefaultFormValues: () => {
        return DEFAULT_CORE_SEARCH_FORM;
    },
});
interface ISearchContextValue<ExtraFormValues extends object = {}> {
    results: ILoadable<ISearchResponse>;

    domainSearchResponse: Record<string, ILoadable<ISearchResponse>>;

    form: ISearchForm<ExtraFormValues>;

    /**
     * Get all of the DOM elements for selecting query values for a search domain.
     * @param domain The search domain to get values for.
     */
    getFilterComponentsForDomain(domain: string): React.ReactNode;

    /**
     * Update some form values.
     */
    updateForm(updateValues: Partial<ISearchForm<ExtraFormValues>>): void;

    /**
     * Update some form values.
     */
    resetForm(): void;

    /**
     * Perform a search.
     */
    search(): void;

    /**
     * Perform a separate search in any domain outside of the current domain
     */
    searchInDomain(domain: string): void;

    /**
     * Get all of the regitered search domains.
     */
    getDomains(): ISearchDomain[];

    /**
     * Get the current search domain of the form.
     */
    getCurrentDomain(): ISearchDomain;

    /**
     * Get the default values for the form.
     */
    getDefaultFormValues(): ISearchForm;
}

export function useSearchForm<T extends object>() {
    return useContext(SearchContext) as ISearchContextValue<T>;
}
