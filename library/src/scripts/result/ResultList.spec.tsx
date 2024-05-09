/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor, screen } from "@testing-library/react";
import Result, { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { SearchFixture } from "@library/search/__fixtures__/Search.fixture";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import MEMBERS_SEARCH_DOMAIN from "@dashboard/components/panels/MembersSearchDomain";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import EVENT_SEARCH_DOMAIN from "@groups/search/EventSearchDomain";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";
import { SearchService } from "@library/search/SearchService";
import { MemoryRouter } from "react-router";
import { expect } from "@storybook/test";

beforeAll(() => {
    COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
    COMMUNITY_SEARCH_SOURCE.addDomain(EVENT_SEARCH_DOMAIN);
    COMMUNITY_SEARCH_SOURCE.addDomain(PLACES_SEARCH_DOMAIN);
    COMMUNITY_SEARCH_SOURCE.addDomain(MEMBERS_SEARCH_DOMAIN);
    SearchService.addSource(COMMUNITY_SEARCH_SOURCE);
});
describe("ResultList", () => {
    it("Renders empty result list", async () => {
        const { findByText, debug } = render(<ResultList results={[]} rel={"noindex nofollow"} />);
        const noResults = await findByText("No results found.");
        expect(noResults).toBeInTheDocument();
    });
    it("Renders result list without images", async () => {
        const results = SearchFixture.createMockSearchResults(1);

        render(
            <MemoryRouter>
                <SearchFormContextProvider>
                    <ResultList results={results.results} rel={"noindex nofollow"} />
                </SearchFormContextProvider>
            </MemoryRouter>,
        );

        await vi.dynamicImportSettled();
        const testResult = await screen.findByText("test result 0");
        expect(testResult).toBeInTheDocument();
        const imageNodes = screen.queryAllByRole("img");
        expect(imageNodes.length).toBe(0);
    });
    it("Renders result list with image source", async () => {
        const results = SearchFixture.createMockSearchResults(1, {
            result: {
                image: {
                    url: "test-image-url",
                    alt: "test-image-alt",
                },
            },
        });

        render(
            <MemoryRouter>
                <SearchFormContextProvider>
                    <ResultList
                        results={results.results.map((result): IResult => {
                            return {
                                ...result,
                                image: result.image?.url,
                                imageSet: result.image?.urlSrcSet as any,
                            };
                        })}
                        rel={"noindex nofollow"}
                    />
                </SearchFormContextProvider>
            </MemoryRouter>,
        );

        await vi.dynamicImportSettled();

        const imageNodes = screen.getAllByRole("img");
        expect(imageNodes).toHaveLength(1);
        expect(imageNodes[0]).toHaveAttribute("src", "test-image-url");
    });
});
