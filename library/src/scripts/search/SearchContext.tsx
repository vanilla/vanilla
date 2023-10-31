import { DEFAULT_CORE_SEARCH_FORM, INITIAL_SEARCH_STATE, ISearchState } from "@library/search/searchReducer";
import { ISearchForm, ISearchResult } from "@library/search/searchTypes";
import React, { useContext } from "react";
import { TypeAllIcon } from "@library/icons/searchIcons";
import SearchDomain from "@library/search/SearchDomain";
import { EMPTY_SEARCH_DOMAIN_KEY } from "./searchConstants";
import { IResult } from "@library/result/Result";

export const EMPTY_SEARCH_DOMAIN = new (class EmptySearchDomain extends SearchDomain {
    public key = EMPTY_SEARCH_DOMAIN_KEY;
    public sort = 0;
    public name = "All";
    public icon = (<TypeAllIcon />);
    public recordTypes = [];
})();

export const SearchContext = React.createContext<ISearchContextValue>({
    updateForm: () => {},
    resetForm: () => {},
    response: INITIAL_SEARCH_STATE.response,
    domainSearchResponse: INITIAL_SEARCH_STATE.domainSearchResponse,
    form: INITIAL_SEARCH_STATE.form,
    search: async () => {},
    domains: [EMPTY_SEARCH_DOMAIN],
    currentDomain: EMPTY_SEARCH_DOMAIN,
    handleSourceChange: (nextSource: string) => {},
    defaultFormValues: DEFAULT_CORE_SEARCH_FORM,
});
interface ISearchContextValue<ExtraFormValues extends object = {}> extends ISearchState<ExtraFormValues> {
    /**
     * Update some form values.
     */
    updateForm(updateValues: Partial<ISearchForm<ExtraFormValues>>): void;

    /**
     * Reset all form values.
     */
    resetForm(): void;

    /**
     * Perform a search.
     */
    search(): Promise<void>;

    /**
     * Get all the available search domains.
     */
    domains: SearchDomain[];

    /**
     * Get the current search domain.
     */
    currentDomain: SearchDomain;

    /**
     * Handle changing SearchSource
     */
    handleSourceChange: (nextSourceKey: string) => void;

    /**
     * Get the default values for the form.
     */
    defaultFormValues: ISearchForm;
}

export function useSearchForm<T extends object>() {
    return useContext(SearchContext) as ISearchContextValue<T>;
}
