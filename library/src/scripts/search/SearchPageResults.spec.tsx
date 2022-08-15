/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor } from "@testing-library/react";
import { SearchFixture } from "@library/search/__fixtures__/Search.fixture";
import { mapResult } from "@library/search/SearchPageResults";

describe("SearchPageResults", () => {
    it("mapResults: without image is undefined", () => {
        const resultWithoutImage = SearchFixture.createMockSearchResults(3);
        expect(resultWithoutImage.results.map(mapResult)[0]?.["image"]).toBeFalsy();
    });
    it("mapResults: with image has URL value", () => {
        const resultWithImage = SearchFixture.createMockSearchResults(3, {
            result: {
                image: {
                    url: "test-image-url",
                    alt: "test-image-alt",
                },
            },
        });
        expect(resultWithImage.results.map(mapResult)[0]).toHaveProperty("image");
        expect(resultWithImage.results.map(mapResult)[0]?.["image"]).toBe("test-image-url");
    });
    it("mapResults: with source set has image URL string fallback", () => {
        const resultWithImageAndSourceSet = SearchFixture.createMockSearchResults(3, {
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
        expect(resultWithImageAndSourceSet.results.map(mapResult)[0]).toHaveProperty("image");
        expect(resultWithImageAndSourceSet.results.map(mapResult)[0]?.["image"]).toBe("test-image-url");
    });
    it("mapResults: with source set has valid source set string", () => {
        const resultWithImageAndSourceSet = SearchFixture.createMockSearchResults(3, {
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
        const imageSet = resultWithImageAndSourceSet.results.map(mapResult)[0]?.["imageSet"];
        expect(imageSet).toBeTruthy();
        expect(imageSet?.split(",").length).toBe(3);
        expect(imageSet?.split(",")[0]).toBe("test-image-10 10w");
        expect(imageSet?.split(",")[1]).toBe("test-image-800 800w");
        expect(imageSet?.split(",")[2]).toBe("test-image-1200 1200w");
    });
});
