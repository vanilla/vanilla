/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ISearchForm, ISearchResult, ISearchResponse, IArticlesSearchResult } from "@library/search/searchTypes";
import { ISearchRequestQuery, ISearchSource } from "@library/search/searchTypes";
import SearchDomain from "@library/search/SearchDomain";
import { TypeAllIcon } from "@library/icons/searchIcons";
import { SearchDomainLoadable } from "../SearchDomainLoadable";
import { fn } from "@storybook/test";

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

export class MockSearchSource implements ISearchSource {
    public label = "Mock Search Source Label";
    public key = "mockSearchKey";
    public endpoint = "/mock-search-endpoint";
    public results: [];

    private abortController: AbortController;

    constructor() {
        this.abortController = new AbortController();
    }

    public abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    public domains: SearchDomain[] = [];

    addDomain(domain: SearchDomain) {
        if (!this.domains.find(({ key }) => key === domain.key)) {
            this.domains.push(domain);
        }
    }

    public performSearch = fn(async function (requestParams: ISearchRequestQuery, endpointOverride?: string) {
        return {
            results: [],
            pagination: {},
        };
    });
}

export class MockSearchSourceWithAsyncDomains implements ISearchSource {
    private abortController: AbortController;

    constructor() {
        this.abortController = new AbortController();
    }

    abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    get key() {
        return "mockSearchSourceWithAsyncDomainsKey";
    }

    get label() {
        return "Mock Async Search Source";
    }

    //mock api response for a custom connected search
    public performSearch = fn(async (requestParams: ISearchRequestQuery, endpointOverride?: string) => {
        return {
            results: [],
            pagination: {},
        };
    });

    private asyncDomains: SearchDomainLoadable[] = [];

    private loadedDomains: SearchDomain[] = [];

    public addDomain = (loadable: SearchDomainLoadable) => {
        this.asyncDomains.push(loadable);
    };

    get domains(): SearchDomain[] {
        return this.loadedDomains;
    }

    public clearDomains() {
        this.loadedDomains = [];
        this.asyncDomains = [];
    }

    public loadDomains = async (): Promise<SearchDomain[]> => {
        return await Promise.all(
            Array.from(this.asyncDomains).map((asyncDomain) => {
                return new Promise<SearchDomain>((resolve) => {
                    if (asyncDomain.loadedDomain) {
                        this.pushDomain(asyncDomain.loadedDomain);

                        return resolve(asyncDomain.loadedDomain);
                    } else {
                        return asyncDomain.load().then((loaded) => {
                            this.pushDomain(loaded);
                            return resolve(loaded);
                        });
                    }
                });
            }),
        );
    };

    private pushDomain(domain: SearchDomain) {
        if (!this.loadedDomains.find(({ key }) => key === domain.key)) {
            this.loadedDomains.push(domain);
        }
    }
}

//represents shrunk mock of ConnectedSearchSource class, with fake api response
export class MockConnectedSearchSource implements ISearchSource {
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

    public domains = [];

    public addDomain() {}

    public abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    //mock api response for a custom connected search
    public performSearch = fn(async (requestParams: ISearchRequestQuery, endpointOverride?: string) => {
        return {
            results: this.results,
            pagination: {},
        };
    });
}

export const MOCK_SEARCH_DOMAIN = new (class MockSearchDomain extends SearchDomain {
    public key = "mockSearchDomain";
    public sort = 0;
    public name = "Mock Search Domain";
    public icon = (<TypeAllIcon />);
    public recordTypes = [];
    public isIsolatedType = false;
    public transformFormToQuery = fn(function (form) {
        return {
            ...form,
        };
    });
})();

export const MOCK_ASYNC_SEARCH_DOMAIN_LOADABLE = new (class MockAsyncSearchDomain extends SearchDomain {
    public key = "mockAsyncSearchDomain";
    public sort = 0;
    public name = "Mock Async Search Domain";
    public icon = (<TypeAllIcon />);
    public recordTypes = [];
    public isIsolatedType = false;
    public transformFormToQuery = fn(function (form) {
        return {
            ...form,
        };
    });
})();

export const MOCK_ASYNC_SEARCH_DOMAIN = new SearchDomainLoadable("mockAsyncSearchDomain", async () => ({
    default: MOCK_ASYNC_SEARCH_DOMAIN_LOADABLE,
}));
