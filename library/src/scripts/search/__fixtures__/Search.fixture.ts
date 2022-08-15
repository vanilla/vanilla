/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchForm, ISearchResult, ISearchResults } from "@library/search/searchTypes";

interface IParams {
    pagination?: ISearchResults["pagination"];
    result?: Partial<ISearchResult>;
}

/**
 * Utilities for testing search
 */
export class SearchFixture {
    /**
     * Create search form
     */
    public static createMockSearchForm(params?: Partial<ISearchForm>): ISearchForm {
        return {
            domain: "test-domain",
            query: "test-query",
            page: 1,
            sort: "relevance",
            initialized: true,
            ...params,
        };
    }

    /**
     * Create a single search result
     */
    public static createMockSearchResult(id: number = 1, params?: Partial<ISearchResult>): ISearchResult {
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
            insertUserID: 0,
            insertUser: {
                userID: 1,
                name: "Bob",
                photoUrl: "",
                dateLastActive: "2016-07-25 17:51:15",
            },
            updateUserID: 0,
            ...params,
        };
    }

    /**
     * Create a mock search response
     */

    public static createMockSearchResults(numberOfResults: number = 14, params: IParams = {}): ISearchResults {
        return {
            results: Array(numberOfResults)
                .fill(null)
                .map((_, id) => this.createMockSearchResult(id, params.result)),

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
