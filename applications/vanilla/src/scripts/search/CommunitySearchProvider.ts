/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchOptionProvider } from "@library/contexts/SearchContext";
import { getSiteSection } from "@library/utility/appUtils";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ISearchOptionData } from "@library/features/search/SearchOption";
import pDebounce from "p-debounce";
import { ALL_CONTENT_DOMAIN_KEY } from "@library/search/searchConstants";
import { ISearchRequestQuery } from "@library/search/searchTypes";
import { SearchService } from "@library/search/SearchService";
import { getCurrentLocale } from "@vanilla/i18n";
import { makeSearchUrl } from "@library/search/SearchUtils";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";

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
            domain: ALL_CONTENT_DOMAIN_KEY,
            query: value,
            expand: ["breadcrumbs", "-body"],
            limit: Math.floor(10 / SearchService.sources.length),
            collapse: true,
            locale: getCurrentLocale(),
            ...options,
        };

        //siteSection information
        const siteSection = getSiteSection();
        //include siteSectionGroup in query object if not the default (for proper kb articles filter)
        const siteSectionGroup = siteSection && siteSection.sectionGroup;
        if (!("siteSectionGroup" in queryObj) && siteSectionGroup !== "vanilla") {
            queryObj["siteSectionGroup"] = siteSectionGroup;
        }

        // if searching in a specific domain (e.g. discussions only), we want to skip searching external sources
        const shouldSearchAllSources = queryObj.domain === ALL_CONTENT_DOMAIN_KEY;

        // TODO [VNLA-1313]: Fix this so that we don't need to wait for all calls to resolve before showing the results
        const searchSources = SearchService.sources
            .filter(shouldSearchAllSources ? () => true : ({ key }) => key === COMMUNITY_SEARCH_SOURCE.key)
            .map((source) =>
                source.performSearch(queryObj).then((response) => ({
                    ...response,
                    source: source.key,
                })),
            );

        const responses = await Promise.all(searchSources);

        const formattedResponses = responses
            .map(({ results, source }) => {
                return results.map((result) => {
                    const data: ISearchOptionData = {
                        crumbs: result.breadcrumbs ?? [],
                        name: result.name,
                        dateUpdated: result.dateUpdated ?? result.dateInserted,
                        labels: result.labelCodes,
                        url: result.url,
                        type: result.type,
                        isForeign: result.isForeign,
                    };
                    return {
                        label: result.name,
                        value: result.name,
                        type: result.type,
                        url: result.url,
                        source: source,
                        data,
                    };
                });
            })
            .flat();

        return formattedResponses;
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

    public makeSearchUrl = (query: string, options: Record<string, any>, externalSearchQuery?: string) =>
        makeSearchUrl(query, options, externalSearchQuery);
}
