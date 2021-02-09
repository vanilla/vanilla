/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchOptionProvider } from "@library/contexts/SearchContext";
import { formatUrl } from "@library/utility/appUtils";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ISearchOptionData } from "@library/features/search/SearchOption";
import { AxiosResponse } from "axios";
import apiv2 from "@library/apiv2";
import pDebounce from "p-debounce";
import { NEW_SEARCH_PAGE_ENABLED } from "@vanilla/library/src/scripts/search/searchConstants";
import { ISearchRequestQuery, ISearchResult } from "@vanilla/library/src/scripts/search/searchTypes";

/**
 * Advanced Search implementation of autocomplete using sphinx.
 */
export class CommunitySearchProvider implements ISearchOptionProvider {
    public supportsAutoComplete = true;

    /**
     * Simple data loading function for the search bar/react-select.
     */
    private fetchSearch = async (value: string, options = {}): Promise<Array<IComboBoxOption<ISearchOptionData>>> => {
        const queryObj: ISearchRequestQuery = {
            query: value,
            expand: ["breadcrumbs", "-body"],
            limit: 10,
            collapse: true,
            ...options,
        };
        const response: AxiosResponse<ISearchResult[]> = await apiv2.get(`/search`, { params: queryObj });
        return response.data.map((result) => {
            const data: ISearchOptionData = {
                crumbs: result.breadcrumbs ?? [],
                name: result.name,
                dateUpdated: result.dateUpdated ?? result.dateInserted,
                labels: result.labelCodes,
                url: result.url,
                type: result.type,
            };
            return {
                label: result.name,
                value: result.name,
                data,
                type: result.type,
                url: result.url,
            };
        });
    };

    /**
     * A debounced version of the fetchSearch() function.
     */
    private debounceFetchSearch = pDebounce(this.fetchSearch, 100);

    /**
     * Get autocomplete results.
     *
     * This has an early bailout for an empty string because initially focusing the input can cause
     * a change event to be fired with an empty value.
     *
     * @see https://github.com/JedWatson/react-select/issues/614#issuecomment-380763225
     */
    public autocomplete = (query: string, options = {}) => {
        if (query === "") {
            return Promise.resolve([]);
        }

        return this.debounceFetchSearch(query, options);
    };

    public makeSearchUrl = (query: string) => {
        const queryParamName = NEW_SEARCH_PAGE_ENABLED ? "query" : "search";
        return formatUrl(`/search?${queryParamName}=${query}`, true);
    };
}
