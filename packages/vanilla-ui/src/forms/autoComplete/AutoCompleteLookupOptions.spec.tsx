/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { renderHook, act } from "@testing-library/react-hooks";
import { useApiLookup } from "./AutoCompleteLookupOptions";
import { mockAPI } from "@library/__tests__/utility";
import apiv2 from "@library/apiv2";

const mockAdapter = mockAPI();

jest.mock("@vanilla/react-utils", () => ({
    useIsMounted: () => {
        return () => true;
    },
}));

jest.setTimeout(10000);
jest.useFakeTimers();

const makeSingleResponse = (id: number) => {
    return {
        value: id,
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

describe("AutoCompleteLookupOptions", () => {
    beforeEach(() => {
        mockAdapter.reset();
    });
    it("Options from search url are available for unpopulated input", async () => {
        mockAdapter.onGet(/vanillaforums\.tld\/search/).reply(200, makeSearchResponse(7));

        const lookup = {
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

        await act(async () => {
            const { result, unmount, waitForNextUpdate } = renderHook(() => {
                return useApiLookup(lookup, apiv2, "", "", "");
            });

            jest.runAllTimers();

            await waitForNextUpdate();

            // There should be 3 items in the options
            expect(result.current[0]?.length).toBe(7);
            unmount();
        });
    });

    it("Options from search url are available for populated single input", async () => {
        mockAdapter.onGet(/vanillaforums\.tld\/search/).reply(200, makeSearchResponse(3));
        mockAdapter.onGet(/vanillaforums\.tld\/single\/21/).reply(200, makeSingleResponse(21));

        const lookup = {
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

        await act(async () => {
            const { result, unmount, waitForNextUpdate } = renderHook(() => {
                return useApiLookup(lookup, apiv2, "21", "21", "21");
            });

            jest.runAllTimers();

            await waitForNextUpdate();

            // There should be 3 items in the options
            expect(result.current[0]?.length).toBe(3);
            unmount();
        });
    });

    it("Options from search url are available for populated multi-input", async () => {
        mockAdapter.onGet(/vanillaforums\.tld\/search/).reply(200, makeSearchResponse(3));
        mockAdapter.onGet(/vanillaforums\.tld\/single\/42/).reply(200, makeSingleResponse(42));
        mockAdapter.onGet(/vanillaforums\.tld\/single\/78/).reply(200, makeSingleResponse(78));

        const lookup = {
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

        await act(async () => {
            const { result, unmount, waitForNextUpdate } = renderHook(() => {
                return useApiLookup(lookup, apiv2, [42, 78], [42, 78], [42, 78]);
            });

            jest.runAllTimers();

            await waitForNextUpdate();

            // There should be 5 items in the options
            expect(result.current[0]?.length).toBeGreaterThan(5);
            unmount();
        });
    });

    it("Requests each ID in array", async () => {
        mockAdapter.onGet(/vanillaforums\.tld\/single\/1/).reply(200, makeSingleResponse(1));
        mockAdapter.onGet(/vanillaforums\.tld\/single\/2/).reply(200, makeSingleResponse(2));

        const lookup = {
            searchUrl: "http://vanillaforums.tld/search/%s",
            singleUrl: "http://vanillaforums.tld/single/%s",
            valueKey: "value",
            labelKey: "label",
            extraLabelKey: "extraLabel",
            resultsKey: "results",
            excludeLookups: ["exclude"],
            processOptions: (options: any) => options,
            group: "group",
        };

        await act(async () => {
            const { result, unmount, waitForNextUpdate } = renderHook(() => {
                return useApiLookup(lookup, apiv2, [1, 2], [1, 2], [1, 2]);
            });

            jest.runAllTimers();

            await waitForNextUpdate();

            // Verify options are updated with both values
            const options = result.current[0] ?? [];
            [1, 2].forEach((id) => {
                const option = options.find((o) => o.value === id);
                expect(option).toBeTruthy();
            });
            unmount();
        });
    });
});
