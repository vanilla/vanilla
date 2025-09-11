/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MentionsProvider, useMentions } from "./MentionsContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";

import { IUserFragment } from "@library/@types/api/users";
import React from "react";
import { renderHook } from "@testing-library/react-hooks";
import { setMeta } from "@library/utility/appUtils";
import { waitFor } from "@testing-library/react";

// Mock user suggestions data
const mockUserSuggestions: IUserFragment[] = [
    {
        userID: 1,
        name: "Vans",
        photoUrl: "https://example.com/vans.jpg",
        dateLastActive: "2024-01-01T00:00:00Z",
    },
    {
        userID: 2,
        name: "Van Gogh",
        photoUrl: "https://example.com/vangogh.jpg",
        dateLastActive: "2024-01-01T00:00:00Z",
    },
    {
        userID: 3,
        name: "Vanilla",
        photoUrl: "https://example.com/vanilla.jpg",
        dateLastActive: "2024-01-01T00:00:00Z",
    },
];

function createWrapper(mentionsProps?: { recordID?: number; recordType?: string }) {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    function TestWrapper({ children }: { children: React.ReactNode }) {
        return (
            <QueryClientProvider client={queryClient}>
                <MentionsProvider {...mentionsProps}>{children}</MentionsProvider>
            </QueryClientProvider>
        );
    }

    return TestWrapper;
}

describe("MentionsContext", () => {
    beforeEach(() => {
        // Set meta values
        setMeta("mentions.enabled", true);
        setMeta("siteSection", {
            sectionID: "test-section",
            basePath: "/test",
            contentLocale: "en",
            sectionGroup: "test-group",
            name: "Test Section",
            apps: {},
            attributes: {},
        });

        const mock = mockAPI();

        // Mock the /users/by-names/ endpoint
        mock.onGet("/users/by-names/").reply((config) => {
            const params = config.params;
            if (params && params.name === "Van*") {
                return [200, mockUserSuggestions];
            }
            return [200, []];
        });

        applyAnyFallbackError(mock);
    });

    it("when username is null, the suggestedUsers is an empty array", async () => {
        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper(),
        });

        expect(result.current.username).toBeNull();
        expect(result.current.suggestedUsers).toEqual([]);
    });

    it("when username has some value, users are suggested", async () => {
        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper(),
        });

        // Set username to "Van"
        result.current.setUsername("Van");

        // Wait for the API call and data to load
        await waitFor(() => {
            expect(result.current.username).toBe("Van");
        });

        await waitFor(() => {
            expect(result.current.suggestedUsers).toHaveLength(3);
            expect(result.current.suggestedUsers[0].name).toBe("Vans");
            expect(result.current.suggestedUsers[1].name).toBe("Van Gogh");
            expect(result.current.suggestedUsers[2].name).toBe("Vanilla");
        });
    });

    it("when resetUsername is called, the suggestedUsers are emptied", async () => {
        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper(),
        });

        // First set username to "Van"
        result.current.setUsername("Van");

        // Wait for suggestions to load
        await waitFor(() => {
            expect(result.current.suggestedUsers).toHaveLength(3);
        });

        // Reset username
        result.current.resetUsername();

        // Wait for suggestions to be cleared
        await waitFor(() => {
            expect(result.current.username).toBeNull();
            expect(result.current.suggestedUsers).toEqual([]);
        });
    });

    it("includes recordType and recordID in params when both are provided", async () => {
        const mock = mockAPI();
        let capturedParams: any = null;

        mock.onGet("/users/by-names/").reply((config) => {
            capturedParams = config.params;
            return [200, mockUserSuggestions];
        });

        applyAnyFallbackError(mock);

        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper({ recordType: "category", recordID: 123 }),
        });

        result.current.setUsername("Van");

        await waitFor(() => {
            expect(capturedParams).toBeTruthy();
            expect(capturedParams.recordType).toBe("category");
            expect(capturedParams.recordID).toBe(123);
        });
    });

    it("excludes recordType and recordID from params when only recordType is provided", async () => {
        const mock = mockAPI();
        let capturedParams: any = null;

        mock.onGet("/users/by-names/").reply((config) => {
            capturedParams = config.params;
            return [200, mockUserSuggestions];
        });

        applyAnyFallbackError(mock);

        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper({ recordType: "category" }),
        });

        result.current.setUsername("Van");

        await waitFor(() => {
            expect(capturedParams).toBeTruthy();
            expect(capturedParams.recordType).toBeUndefined();
            expect(capturedParams.recordID).toBeUndefined();
        });
    });

    it("excludes recordType and recordID from params when only recordID is provided", async () => {
        const mock = mockAPI();
        let capturedParams: any = null;

        mock.onGet("/users/by-names/").reply((config) => {
            capturedParams = config.params;
            return [200, mockUserSuggestions];
        });

        applyAnyFallbackError(mock);

        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper({ recordID: 123 }),
        });

        result.current.setUsername("Van");

        await waitFor(() => {
            expect(capturedParams).toBeTruthy();
            expect(capturedParams.recordType).toBeUndefined();
            expect(capturedParams.recordID).toBeUndefined();
        });
    });

    it("excludes recordType and recordID from params when neither is provided", async () => {
        const mock = mockAPI();
        let capturedParams: any = null;

        mock.onGet("/users/by-names/").reply((config) => {
            capturedParams = config.params;
            return [200, mockUserSuggestions];
        });

        applyAnyFallbackError(mock);

        const { result } = renderHook(() => useMentions(), {
            wrapper: createWrapper(),
        });

        result.current.setUsername("Van");

        await waitFor(() => {
            expect(capturedParams).toBeTruthy();
            expect(capturedParams.recordType).toBeUndefined();
            expect(capturedParams.recordID).toBeUndefined();
        });
    });
});
