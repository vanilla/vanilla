import { DEFAULT_CORE_SEARCH_FORM, INITIAL_SEARCH_STATE } from "@library/search/searchReducer";
import { ISearchForm, ISearchResults, ISearchFormBase } from "@library/search/searchTypes";
import { ILoadable } from "@vanilla/library/src/scripts/@types/api/core";
import React, { useContext } from "react";
import { ISearchDomain } from "./SearchService";

export const SearchContext = React.createContext<ISearchContextValue>({
    getFilterComponentsForDomain: () => null,
    updateForm: () => {},
    resetForm: () => {},
    results: INITIAL_SEARCH_STATE.results,
    domainSearchResults: INITIAL_SEARCH_STATE.domainSearchResults,
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
interface ISearchContextValue<ExtraFormValues extends object = ISearchFormBase> {
    results: ILoadable<ISearchResults>;

    domainSearchResults: Record<string, ILoadable<ISearchResults>>;

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
