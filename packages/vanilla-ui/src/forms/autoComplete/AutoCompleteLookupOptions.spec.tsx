/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { renderHook, RenderHookResult } from "@testing-library/react-hooks";
import { useApiLookup } from "./AutoCompleteLookupOptions";
import { mockAPI } from "@library/__tests__/utility";
import apiv2 from "@library/apiv2";
import MockAdapter from "axios-mock-adapter";

const makeSingleResponse = (id: string) => {
    return {
        value: parseInt(id),
        label: `label ${id}`,
    };
};

const makeSearchResponse = (amount: number) => {
    return Array(amount)
        .fill(null)
        .map((_, i) => {
            return {
                value: i,
                label: `label ${i}`,
            };
        });
};

const mockLookup = {
    searchUrl: "http://vanillaforums.tld/search/%s",
    singleUrl: "http://vanillaforums.tld/single/%s",
    valueKey: "value",
    labelKey: "label",
    extraLabelKey: "extraLabel",
    resultsKey: ".",
    excludeLookups: ["exclude"],
    processOptions: (options: any) => options,
    group: "group",
};

describe("useApiLookup", () => {
    let mockAdapter: MockAdapter;

    beforeAll(() => {
        mockAdapter = mockAPI();
    });

    describe("handleSearch", () => {
        const mockSearchTerm = "mock search term";
        const searchResponse = makeSearchResponse(7);
        let hookResult: RenderHookResult<unknown, ReturnType<typeof useApiLookup>>;

        beforeAll(async () => {
            mockAdapter.onGet(/vanillaforums\.tld\/search/).reply(200, searchResponse);
            mockAdapter.onGet(/\/vanillaforums\.tld\/single\/\d+/).reply((config) => {
                const id = config.url?.split("/").pop();
                return [200, makeSingleResponse(id!)];
            });
            hookResult = renderHook(() => useApiLookup(mockLookup, apiv2));
            await hookResult.result.current.handleSearch(mockSearchTerm);
        });

        afterAll(() => {
            mockAdapter.reset();
        });

        it("Makes a search request", async () => {
            expect(mockAdapter.history.get.length).toBe(1);
        });
        it("saved the results", async () => {
            expect(hookResult.result.current.options.length).toBe(searchResponse.length);
        });

        describe("Caching results", () => {
            it("Will not make a second search request for the same search term", async () => {
                await hookResult.result.current.handleSearch(mockSearchTerm);
                expect(mockAdapter.history.get.length).toBe(1);
            });

            it("Will not make API requests for individual options which it has previously retrieved through search", async () => {
                await hookResult.result.current.loadIndividualOptions([`${searchResponse[0].value}`]);
                expect(mockAdapter.history.get.length).toBe(1);
            });

            it("Will make API requests for individual options which it has not previously retrieved through search", async () => {
                await hookResult.result.current.loadIndividualOptions(["99"]);
                expect(mockAdapter.history.get.length).toBe(2);
            });
        });
    });

    describe("loadIndividualOptions", () => {
        const mockValues = ["1", "2", "3"];
        let hookResult: RenderHookResult<unknown, ReturnType<typeof useApiLookup>>;

        beforeAll(async () => {
            mockAdapter.onGet(/\/vanillaforums\.tld\/single\/\d+/).reply((config) => {
                const id = config.url?.split("/").pop();
                return [200, makeSingleResponse(id!)];
            });
            hookResult = renderHook(() => {
                return useApiLookup(mockLookup, apiv2);
            });
            await hookResult.result.current.loadIndividualOptions(mockValues);
        });

        afterAll(() => {
            mockAdapter.reset();
        });

        it("Makes requests for each value", async () => {
            expect(mockAdapter.history.get.length).toBe(mockValues.length);
            expect(hookResult.result.current.options.length).toBe(mockValues.length);
        });

        it("Will not request excluded values", async () => {
            await hookResult.result.current.loadIndividualOptions([mockLookup.excludeLookups[0]]);
            expect(mockAdapter.history.get.length).toBe(mockValues.length);
        });

        it("Does not make subsequent requests for cached values", async () => {
            await hookResult.result.current.loadIndividualOptions(mockValues.slice(0, 1));
            expect(mockAdapter.history.get.length).toBe(mockValues.length);
        });

        it("Will make subsequent request for non-cached values", async () => {
            await hookResult.result.current.loadIndividualOptions(["98"]);
            expect(mockAdapter.history.get.length).toBe(mockValues.length + 1);
        });
    });
});
