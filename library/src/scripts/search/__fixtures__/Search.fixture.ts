/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchForm, ISearchResult, ISearchResponse, IArticlesSearchResult } from "@library/search/searchTypes";
import { RecordID } from "@vanilla/utils";

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
