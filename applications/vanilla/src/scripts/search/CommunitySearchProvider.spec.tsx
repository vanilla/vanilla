/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommunitySearchProvider } from "./CommunitySearchProvider";
import { mockAPI } from "@library/__tests__/utility";
import { renderHook } from "@testing-library/react-hooks";
import { useSearch } from "@library/contexts/SearchContext";
import { setMeta } from "@library/utility/appUtils";
import { MockConnectedSearchSource, SearchFixture } from "@library/search/__fixtures__/Search.fixture";
import { SearchService } from "@library/search/SearchService";

describe("CommunitySearchProvider.autocomplete()", () => {
    const mockSearchResults = SearchFixture.createMockSearchResults().results;
    const mockSearchResultWithCategory = SearchFixture.createMockSearchResult(17);
    mockSearchResultWithCategory["categoryID"] = 7;
    mockSearchResultWithCategory.type = "comment";
    mockSearchResultWithCategory.name = "comment in site section with categoryID";
    mockSearchResults.push(mockSearchResultWithCategory);

    function checkSearchResultStructure(value) {
        expect(value).toHaveProperty("label");
        expect(value).toHaveProperty("value");
        expect(value).toHaveProperty("type");
        expect(value).toHaveProperty("url");
        expect(value).toHaveProperty("source");
        expect(value).toHaveProperty("data");
    }
    it("Results right output format.", async () => {
        const mockAdapter = mockAPI();
        mockAdapter
            .onGet(/search/)
            .replyOnce(200, mockSearchResults, { "x-app-page-result-count": mockSearchResults.length, link: "#" });
        const { result } = renderHook(() => {
            return useSearch();
        });

        result.current.searchOptionProvider = new CommunitySearchProvider();
        const autocompleteResults = await result.current.searchOptionProvider.autocomplete("t");

        //all search results in the right format
        expect(autocompleteResults.length).toBe(15);
        checkSearchResultStructure(autocompleteResults[0]);
    });
    it("Apply filters for site section.", async () => {
        const mockAdapter = mockAPI();

        mockAdapter.onGet(/search/).replyOnce(
            200,
            mockSearchResults.filter((result) => result["categoryID"] === 7),
            { "x-app-page-result-count": mockSearchResults.length, link: "#" },
        );

        const { result } = renderHook(() => {
            setMeta("siteSection", {
                attributes: {
                    categoryID: 7,
                },
            });
            return useSearch();
        });

        result.current.searchOptionProvider = new CommunitySearchProvider();
        const autocompleteResults = await result.current.searchOptionProvider.autocomplete("t");

        //only the one with site section categoryID
        expect(autocompleteResults.length).toBe(1);
        expect(autocompleteResults[0].label).toBe(mockSearchResultWithCategory.name);
        expect(autocompleteResults[0].value).toBe(mockSearchResultWithCategory.name);
    });
    it("Supports multiple search sources", async () => {
        const mockCustomConnectedSearchApiConfig = {
            label: "test-label",
            endpoint: "https://api.test-site.ca/search",
            searchConnectorID: "test-connector-ID",
        };
        const mockCustomConnectedSearchSourceResult = {
            body: "test body text",
            dateInserted: "2019-08-28T22:56:31Z",
            dateUpdated: "2019-09-18T18:53:14Z",
            highlight: "something here",
            isForeign: true,
            name: "test name",
            recordID: 23,
            recordType: "article",
            type: "customType",
            url: "#",
        };
        const mockConnectedSearchSource = new MockConnectedSearchSource({
            ...mockCustomConnectedSearchApiConfig,
            results: [mockCustomConnectedSearchSourceResult],
        });

        SearchService.addPluggableSource(mockConnectedSearchSource);
        const mockAdapter = mockAPI();
        mockAdapter
            .onGet(/search/)
            .replyOnce(200, mockSearchResults, { "x-app-page-result-count": mockSearchResults.length, link: "#" });

        const { result } = renderHook(() => {
            return useSearch();
        });

        result.current.searchOptionProvider = new CommunitySearchProvider();
        const autocompleteResults = await result.current.searchOptionProvider.autocomplete("t");

        //normal core results are 15, the last one is from custom search connector, and its source is not "community"
        expect(autocompleteResults.length).toBe(16);
        autocompleteResults.forEach((searchResult, index) => {
            if (searchResult["source"] && index < mockSearchResults.length) {
                expect(searchResult["source"]).toBe("community");
            } else {
                expect(searchResult["source"]).not.toBe("community");
                expect(searchResult["source"]).toBe(mockCustomConnectedSearchApiConfig.searchConnectorID);
                expect(searchResult["label"]).toBe(mockCustomConnectedSearchSourceResult.name);
                expect(searchResult["value"]).toBe(mockCustomConnectedSearchSourceResult.name);
                expect(searchResult["type"]).toBe(mockCustomConnectedSearchSourceResult.type);

                //and should have the same structure as regular search result, generated from CommunitySearchProvider
                checkSearchResultStructure(searchResult);
            }
        });
    });
});
