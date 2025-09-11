/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import { act, fireEvent, render, screen, waitFor } from "@testing-library/react";

import { CustomPagesAPI } from "@dashboard/appearance/customPages/CustomPagesApi";
import CustomPagesManagement, { CustomPagesManagementContent } from "./CustomPagesManagement";
import { MemoryRouter, Router } from "react-router-dom";
import { LiveAnnouncer } from "react-aria-live";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { setMeta } from "@library/utility/appUtils";
import {
    CustomPagesContext,
    CustomPagesProvider,
    ICustomPagesContext,
} from "@dashboard/appearance/customPages/CustomPages.context";
import { createMemoryHistory } from "history";

const mockCustomPage: CustomPagesAPI.Page = {
    customPageID: 1,
    seoTitle: "Test Page",
    seoDescription: "Test Description",
    urlcode: "/test-page",
    url: "https://example.com/test-page",
    status: "published",
    siteSectionID: "0",
    roleIDs: [2],
    rankIDs: [],
    layoutID: 100,
};

const mockUnpublishedPage: CustomPagesAPI.Page = {
    ...mockCustomPage,
    customPageID: 2,
    seoTitle: "Not Published Page",
    seoDescription: "Not Plublished Description",
    status: "unpublished",
    layoutID: 101,
};

const mockLayoutData = {
    layout: [
        {
            $hydrate: "react.asset.discussionList",
            apiParams: { limit: 10 },
        },
    ],
    name: "Test Layout",
    titleBar: {
        $hydrate: "react.titleBar",
    },
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false },
    },
});

let mockApiAdapter: ReturnType<typeof mockAPI>;
let history = createMemoryHistory();
const renderInProvider = (contextOverrides?: Partial<ICustomPagesContext>) => {
    setMeta("context.basePath", "");

    const pageToDelete = contextOverrides?.pageToDelete ?? null;
    const setPageToDelete = contextOverrides?.setPageToDelete ?? vi.fn();
    const pageToEdit = contextOverrides?.pageToEdit ?? null;
    const setPageToEdit = contextOverrides?.setPageToEdit ?? vi.fn();
    const pageToCopy = contextOverrides?.pageToCopy ?? null;
    const setPageToCopy = contextOverrides?.setPageToCopy ?? vi.fn();

    render(
        <Router history={history}>
            <TestReduxProvider
                state={{
                    config: {},
                }}
            >
                <LiveAnnouncer>
                    <QueryClientProvider client={queryClient}>
                        <CustomPagesContext.Provider
                            value={{
                                pageToDelete,
                                setPageToDelete,
                                pageToEdit,
                                setPageToEdit,
                                pageToCopy,
                                setPageToCopy,
                            }}
                        >
                            <CustomPagesManagementContent />
                        </CustomPagesContext.Provider>
                    </QueryClientProvider>
                </LiveAnnouncer>
                ,
            </TestReduxProvider>
        </Router>,
    );
};

describe("CustomPagesManagement", () => {
    beforeAll(() => {
        mockApiAdapter = mockAPI();
        mockApiAdapter.onGet(/subcommunities.*/).reply(200, []);
        mockApiAdapter.onGet("/roles").reply(200, []);
        mockApiAdapter.onGet("/ranks").reply(200, []);
    });

    it("renders empty state when there are no custom pages", async () => {
        mockApiAdapter.onGet("/custom-pages").reply(200, []);

        renderInProvider();

        await waitFor(() => {
            expect(screen.getByText("No custom pages")).toBeInTheDocument();
            expect(screen.getByText("Your custom pages will appear here.")).toBeInTheDocument();
        });
    });

    it("renders custom pages with meta when they exist", async () => {
        mockApiAdapter.onGet("/custom-pages").reply(200, [mockCustomPage, mockUnpublishedPage]);

        renderInProvider();

        await waitFor(() => {
            expect(screen.getByText("Test Page")).toBeInTheDocument();
            expect(screen.getByText("Test Description")).toBeInTheDocument();
            expect(screen.getByText("Not Published Page")).toBeInTheDocument();
            expect(screen.getByText("Unpublished")).toBeInTheDocument();
        });
    });

    it("opens create page modal when there is a value in page to edit", async () => {
        mockApiAdapter.onGet("/custom-pages").reply(200, []);

        renderInProvider({ pageToEdit: "new" });

        await waitFor(() => {
            expect(screen.getByRole("heading", { name: "Create Page" })).toBeInTheDocument();
        });
    });

    it("sends correct request body when creating a new page", async () => {
        mockApiAdapter.onGet("/custom-pages").reply(200, []);

        const expectedRequestBody = {
            seoTitle: "New Test Page",
            seoDescription: "New test description",
            urlcode: "/new-test-page",
            siteSectionID: "0",
            roleIDs: [],
            rankIDs: [],
            status: "unpublished",
            layoutData: {
                layout: [],
                name: "New Test Page",
                titleBar: {
                    $hydrate: "react.titleBar",
                },
            },
        };
        let actualRequestBody: any;
        mockApiAdapter.onPost("/custom-pages").reply((config) => {
            actualRequestBody = JSON.parse(config.data);
            return [
                201,
                {
                    ...mockCustomPage,
                    customPageID: 999,
                    layoutID: 201,
                    seoTitle: `Copy of ${mockCustomPage.seoTitle}`,
                },
            ];
        });

        act(() => {
            renderInProvider({ pageToEdit: "new" });
        });

        await waitFor(() => {
            expect(screen.getByRole("heading", { name: "Create Page" })).toBeInTheDocument();
        });

        // Fill form fields
        await waitFor(() => {
            const titleInput = screen.getByLabelText(/Page Title/i);
            const descriptionInput = screen.getByLabelText(/Page Description/i);
            const urlInput = screen.getByLabelText(/Page URL Path/i);

            fireEvent.change(titleInput, { target: { value: "New Test Page" } });
            fireEvent.change(descriptionInput, { target: { value: "New test description" } });
            fireEvent.change(urlInput, { target: { value: "/new-test-page" } });
        });

        // Submit form
        const submitButton = screen.getByRole("button", { name: "Create Page" });
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(actualRequestBody).toEqual(expectedRequestBody);
            expect(history.location.pathname).toBe("/appearance/layouts/customPage/201/edit");
        });
    });

    it("includes copyLayoutID in copy request", async () => {
        mockApiAdapter.onGet("/custom-pages").reply(200, [mockCustomPage]);

        let copyRequestBody: any;
        mockApiAdapter.onPost("/custom-pages").reply((config) => {
            copyRequestBody = JSON.parse(config.data);
            return [
                201,
                {
                    ...mockCustomPage,
                    customPageID: 999,
                    layoutID: 201,
                    seoTitle: `Copy of ${mockCustomPage.seoTitle}`,
                },
            ];
        });

        act(() => {
            renderInProvider({ pageToCopy: mockCustomPage });
        });

        // Fill form fields
        await waitFor(() => {
            const titleInput = screen.getByLabelText(/Page Title/i);
            const descriptionInput = screen.getByLabelText(/Page Description/i);
            const urlInput = screen.getByLabelText(/Page URL Path/i);

            fireEvent.change(titleInput, { target: { value: "Copied Test Page" } });
            fireEvent.change(descriptionInput, { target: { value: "Copies test description" } });
            fireEvent.change(urlInput, { target: { value: "/copied-test-page" } });
        });

        // Submit form
        const submitButton = screen.getByRole("button", { name: "Copy Page" });
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(copyRequestBody).toMatchObject(
                expect.objectContaining({
                    copyLayoutID: mockCustomPage.layoutID,
                }),
            );
        });
    });
});
