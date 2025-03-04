import { DEFAULT_CORE_SEARCH_FORM, INITIAL_SEARCH_STATE, ISearchState } from "@library/search/searchReducer";
import { ISearchForm, ISearchSource } from "@library/search/searchTypes";
import React, { useContext } from "react";
import SearchDomain from "@library/search/SearchDomain";
import { EMPTY_SEARCH_DOMAIN_KEY } from "./searchConstants";
import { SearchService } from "./SearchService";
import { Icon } from "@vanilla/icons";

export const EMPTY_SEARCH_DOMAIN = new (class EmptySearchDomain extends SearchDomain {
    public key = EMPTY_SEARCH_DOMAIN_KEY;
    public sort = 0;
    public name = "All";
    public icon = (<Icon icon="search-all" />);
    public recordTypes = [];
})();

export const SearchFormContext = React.createContext<ISearchFormContextValue>({
    updateForm: () => {},
    resetForm: () => {},
    response: INITIAL_SEARCH_STATE.response,
    domainSearchResponse: INITIAL_SEARCH_STATE.domainSearchResponse,
    form: INITIAL_SEARCH_STATE.form,
    search: async () => {},
    domains: [EMPTY_SEARCH_DOMAIN],
    currentDomain: EMPTY_SEARCH_DOMAIN,
    handleSourceChange: async (nextSource: string) => {},
    defaultFormValues: DEFAULT_CORE_SEARCH_FORM,
    currentSource: SearchService.sources[0] ?? undefined,
});
interface ISearchFormContextValue<ExtraFormValues extends object = {}> extends ISearchState<ExtraFormValues> {
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
    handleSourceChange: (nextSourceKey: string) => Promise<void>;

    /**
     * Get the default values for the form.
     */
    defaultFormValues: ISearchForm;

    currentSource: ISearchSource | undefined; //TODO: document
}

export function useSearchForm<T extends object>() {
    return useContext(SearchFormContext) as ISearchFormContextValue<T>;
}
