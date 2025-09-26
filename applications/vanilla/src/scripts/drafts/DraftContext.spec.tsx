/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DraftContextProvider, useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { renderHook } from "@testing-library/react-hooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { act } from "@testing-library/react-hooks";
import { ComponentProps } from "react";
import { DraftRecordType } from "@vanilla/addon-vanilla/drafts/types";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
            enabled: true,
        },
    },
});

let mockApi: MockAdapter;

const draftContextProps: ComponentProps<typeof DraftContextProvider> = {
    recordType: DraftRecordType.COMMENT,
    parentRecordID: 0,
};

const MOCK_PATH_NAME = "/discussion/mock-discussion-id/";

describe("DraftContext", () => {
    beforeAll(() => {
        vi.useFakeTimers();
        localStorage.clear();
    });
    beforeEach(() => {
        mockApi = mockAPI();
        vitest.mock("react-router-dom", () => ({
            ...vitest.importActual("react-router-dom"),
            useLocation: () => ({
                pathname: MOCK_PATH_NAME,
            }),
        }));
        vi.spyOn(window, "requestAnimationFrame").mockImplementation((cb: any) => {
            return cb();
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });
    it("updateDraft saves to local storage", async () => {
        const wrapper = ({ children }) => (
            <QueryClientProvider client={queryClient}>
                <DraftContextProvider {...draftContextProps}>{children}</DraftContextProvider>
            </QueryClientProvider>
        );

        const expectedDraft = {
            attributes: {
                body: '[{"type":"p","children":[{"text":"Hello World"}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        };

        const { result } = renderHook(() => useDraftContext(), {
            wrapper,
        });

        await act(async () => {
            result.current.updateDraft(expectedDraft as any);
            vi.advanceTimersByTime(5000);
        });

        expect(result.current.draftID).toBeNull();
        expect(result.current.draft).toMatchObject(expectedDraft);

        const localStore = localStorage.getItem("vanilla//draftStore");
        expect(localStore).not.toBeNull();
        const parsed = localStore && JSON.parse(localStore);
        expect(parsed).toMatchObject({
            [MOCK_PATH_NAME]: expectedDraft,
        });
    });

    it("updateDraft persists to server after delay", async () => {
        const wrapper = ({ children }) => (
            <QueryClientProvider client={queryClient}>
                <DraftContextProvider {...draftContextProps}>{children}</DraftContextProvider>
            </QueryClientProvider>
        );

        const expectedDraft = {
            attributes: {
                body: '[{"type":"p","children":[{"text":"Hello World"}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        };

        mockApi.onPost("/drafts").reply(200, expectedDraft);

        const { result } = renderHook(() => useDraftContext(), {
            wrapper,
        });

        await act(async () => {
            result.current.updateDraft(expectedDraft as any);
        });

        vi.advanceTimersByTime(12000);

        expect(mockApi.history.post.length).toBe(1);
    });
    it("disable prevents local storage saves and server sync", async () => {
        const mockDraft = {
            attributes: {
                body: '[{"type":"p","children":[{"text":"Hello World"}]}]',
                draftMeta: {
                    format: "rich2",
                },
                draftType: "comment",
                format: "rich2",
                lastSaved: "2025-02-15T00:00:00.000Z",
            },
            recordType: "comment",
        };

        localStorage.clear();

        const wrapper = ({ children }) => (
            <QueryClientProvider client={queryClient}>
                <DraftContextProvider {...draftContextProps}>{children}</DraftContextProvider>
            </QueryClientProvider>
        );

        const { result } = renderHook(() => useDraftContext(), {
            wrapper,
        });

        await act(async () => {
            result.current.disableAutosave();
        });

        await act(async () => {
            result.current.updateDraft(mockDraft as any);
        });

        expect(result.current.draftID).toBeNull();
        expect(result.current.draft).toBeFalsy();

        const localStore = localStorage.getItem("vanilla//draftStore");
        expect(localStore).toBe("{}");
    });
});
