/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, waitFor } from "@testing-library/react";
import { DEFAULT_RESULT_COMPONENT } from "@library/search/searchConstants";
import ResultList from "@library/result/ResultList";
import { SearchFixture } from "@library/search/__fixtures__/Search.fixture";
import { mapResult } from "@library/search/SearchPageResults";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";

describe("ResultList", () => {
    it("Renders empty result list", async () => {
        const { findByText, debug } = render(
            <ResultList
                resultComponent={DEFAULT_RESULT_COMPONENT}
                results={[]}
                ResultWrapper={undefined}
                rel={"noindex nofollow"}
            />,
        );
        const noResults = await findByText("No results found.");
        expect(noResults).toBeInTheDocument();
    });
    it("Renders result list without images", async () => {
        const results = SearchFixture.createMockSearchResults(1);

        const { findByText, container } = render(
            <SearchFormContextProvider>
                <ResultList
                    resultComponent={DEFAULT_RESULT_COMPONENT}
                    results={results.results.map(mapResult)}
                    ResultWrapper={undefined}
                    rel={"noindex nofollow"}
                />
            </SearchFormContextProvider>,
        );

        waitFor(async () => {
            const testResult = await findByText("test result 1");
            expect(testResult).toBeInTheDocument();
            const imageNodes = container.querySelectorAll("img");
            expect(imageNodes.length).toBe(0);
        });
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

        const { container } = render(
            <SearchFormContextProvider>
                <ResultList
                    resultComponent={DEFAULT_RESULT_COMPONENT}
                    results={results.results.map(mapResult)}
                    ResultWrapper={undefined}
                    rel={"noindex nofollow"}
                />
            </SearchFormContextProvider>,
        );

        waitFor(async () => {
            const imageNodes = container.querySelectorAll("img");
            expect(imageNodes.length).toBe(1);
            expect(imageNodes[0]).toHaveAttribute("src", "test-image-url");
        });
    });
    it("Renders result list with image source set", async () => {
        const results = SearchFixture.createMockSearchResults(1, {
            result: {
                image: {
                    url: "test-image-url",
                    alt: "test-image-alt",
                    urlSrcSet: {
                        10: "test-image-10",
                        800: "test-image-800",
                        1200: "test-image-1200",
                    },
                },
            },
        });

        const { container } = render(
            <SearchFormContextProvider>
                <ResultList
                    resultComponent={DEFAULT_RESULT_COMPONENT}
                    results={results.results.map(mapResult)}
                    ResultWrapper={undefined}
                    rel={"noindex nofollow"}
                />
            </SearchFormContextProvider>,
        );

        waitFor(async () => {
            const imageNodes = container.querySelectorAll("img");
            expect(imageNodes.length).toBe(1);
            expect(imageNodes[0]).toHaveAttribute(
                "srcset",
                "test-image-10 10w,test-image-800 800w,test-image-1200 1200w",
            );
        });
    });
});
