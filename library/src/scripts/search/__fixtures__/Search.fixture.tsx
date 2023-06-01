/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchForm, ISearchResult, ISearchResponse, IArticlesSearchResult } from "@library/search/searchTypes";
import { ALL_CONTENT_DOMAIN_NAME } from "@library/search/searchConstants";
import { ISearchRequestQuery, ISearchSource } from "@library/search/searchTypes";

interface IParams {
    pagination?: ISearchResponse["pagination"];
    result?: Partial<ISearchResult>;
}

/**
 * Utilities for testing search
 */
export class SearchFixture {
    /**
     * Create search form
     */

    public static createMockSearchForm<ExtraFormValues extends object = {}>(params?: ExtraFormValues) {
        return {
            domain: "test-domain",
            query: "test-query",
            page: 1,
            sort: "relevance",
            initialized: true,
            ...(params ?? {}),
        } as ISearchForm<ExtraFormValues>;
    }
    /**
     * Create a single search result
     */
    public static createMockSearchResult(
        id: number = 1,
        params?: Partial<IArticlesSearchResult>,
    ): IArticlesSearchResult {
        return {
            name: `test result ${id}`,
            url: `test-url-${id}`,
            body: "test",
            excerpt: "test",
            recordID: id,
            recordType: "test",
            type: "article",
            breadcrumbs: [],
            dateUpdated: "",
            dateInserted: "",

            insertUser: {
                userID: 1,
                name: "Bob",
                photoUrl: "",
                dateLastActive: "2016-07-25 17:51:15",
            },

            ...params,
        };
    }

    /**
     * Create a mock search response
     */
    public static createMockSearchResults(
        numberOfResults: number = 14,
        params?: { result?: Partial<ISearchResult>; pagination?: Partial<ISearchResponse["pagination"]> },
    ): ISearchResponse {
        return {
            results: Array(numberOfResults)
                .fill(null)
                .map((_, id) => this.createMockSearchResult(id, params?.result)),

            pagination: {
                next: 2,
                prev: 0,
                total: numberOfResults,
                currentPage: 1,
                ...params?.pagination,
            },
        };
    }
}

//represents shrunk mock of ConnectedSearchSource class, with fake api response
export class MockConnectedSearchSource implements ISearchSource {
    public searchableDomainKeys = [ALL_CONTENT_DOMAIN_NAME];
    public defaultDomainKey = ALL_CONTENT_DOMAIN_NAME;

    public label: string;
    public endpoint: string;
    public searchConnectorID: string;
    public results: [];

    private abortController: AbortController;

    constructor(config) {
        this.label = config.label;
        this.endpoint = config.endpoint;
        this.searchConnectorID = config.searchConnectorID;
        this.abortController = new AbortController();
        this.results = config.results;
    }

    get key(): string {
        return this.searchConnectorID;
    }

    public abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    //mock api response for a custom connected search
    public async performSearch(requestParams: ISearchRequestQuery, endpointOverride?: string) {
        const { query } = requestParams;

        if (!query) {
            return {
                results: [],
                pagination: {},
            };
        }

        return {
            results: this.results,
            pagination: {},
        };
    }
}
