/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IGetTagsRequestBody, IGetTagsResponseBody } from "@dashboard/tagging/taggingSettings.types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, waitFor, within } from "@testing-library/react";

import { ITagsApi } from "@dashboard/tagging/Tags.api";
import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import TaggingSettings from "@dashboard/tagging/pages/TaggingSettings";
import { TagsApiContext } from "@dashboard/tagging/TaggingSettings.context";
import { TagsRequestContextProvider } from "@dashboard/tagging/TagsRequest.context";
import { mockTagsResponse } from "@dashboard/tagging/TaggingSettings.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { vitest } from "vitest";
import { MemoryRouter } from "react-router";

const createMockTagsApi = (): ITagsApi => ({
    getTags: vitest.fn().mockImplementation(async (params: IGetTagsRequestBody) => {
        const response: IGetTagsResponseBody = mockTagsResponse;

        // Filter response based on query parameter for more realistic testing
        if (params.query && response.data) {
            const filteredData = response.data.filter((tag) =>
                tag.name.toLowerCase().includes(params.query!.toLowerCase()),
            );
            return {
                ...response,
                data: filteredData,
                paging: {
                    ...response.paging,
                    total: filteredData.length,
                },
            };
        }

        return response;
    }),
    postTag: vitest.fn().mockImplementation(async () => ({} as any)),
    patchTag: vitest.fn().mockImplementation(async () => ({} as any)),
    deleteTag: vitest.fn().mockImplementation(async () => {}),
});

describe("TaggingSettings", () => {
    let queryClient: QueryClient;
    let result: RenderResult;
    let mockTagsApi: ITagsApi;
    let table: HTMLTableElement;

    beforeAll(() => {
        setMeta("tagging.enabled", true);
        setMeta("tagging.scopedTaggingEnabled", true);
    });

    beforeAll(() => {
        TagScopeService.addScope("siteSectionIDs", {
            id: "subcommunity",
            singular: "subcommunity",
            plural: "subcommunities",
            description: "Select the subcommunities to associate this tag with.",
            placeholder: "Select one or more subcommunities",
            getIDs: (tag) => tag.scope?.siteSectionIDs ?? [],
            filterLookupApi: {
                searchUrl: `/subcommunities?name=%s`,
                singleUrl: `/subcommunities/$siteSectionID:%s`,
                labelKey: "name",
                valueKey: "siteSectionID",
            },
            ModalContentComponent: () => <></>,
        });
    });

    beforeEach(() => {
        queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        mockTagsApi = createMockTagsApi();
    });

    function renderTaggingSettings(api: ITagsApi) {
        return render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter>
                    <TagsApiContext.Provider value={{ api }}>
                        <TagsRequestContextProvider>
                            <TaggingSettings />
                        </TagsRequestContextProvider>
                    </TagsApiContext.Provider>
                </MemoryRouter>
            </QueryClientProvider>,
        );
    }

    it("renders the main page title", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        expect(await result.findByText("Tagging")).toBeInTheDocument();
    });

    it("renders a search form", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const searchForm = await result.findByRole("search");
        const searchInput = await within(searchForm).findByRole("textbox");
        expect(searchInput).toBeInTheDocument();
    });

    it("renders the table, with accessible columns", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const table = await result.findByRole("table");
        expect(table).toBeInTheDocument();

        // Column headers should be present
        const tableHeader = within(table).getAllByRole("row")[0];
        expect(within(tableHeader).getByText("Tag Name")).toBeInTheDocument();
        expect(within(tableHeader).getByText("Usage")).toBeInTheDocument();
        expect(within(tableHeader).getByText("Scope")).toBeInTheDocument();
        expect(within(tableHeader).getByText("Date Created")).toBeInTheDocument();
    });

    it("renders the Add Tag button", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const addButton = await result.findByRole("button", { name: "Add Tag" });
        expect(addButton).toBeInTheDocument();
    });

    it("renders Edit Tag buttons for each tag", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const table = await result.findByRole("table");
        const dataRows = within(table).getAllByRole("row").slice(1); // Skip header row

        dataRows.forEach((row) => {
            const buttons = within(row).getAllByRole("button");
            const editButton = buttons[0]; // First button should be edit
            expect(editButton).toBeInTheDocument();
        });
    });

    it("renders Delete Tag buttons for each tag", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const table = await result.findByRole("table");
        const dataRows = within(table).getAllByRole("row").slice(1); // Skip header row

        dataRows.forEach((row) => {
            const deleteButton = within(row).getByLabelText("Delete Tag");
            expect(deleteButton).toBeInTheDocument();
        });
    });

    it("loads and displays tag data", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
            await vitest.waitFor(() => expect(mockTagsApi.getTags).toHaveReturned());
        });

        table = (await result.findByRole("table")) as HTMLTableElement;

        mockTagsResponse.data.forEach((tag, rowIndex) => {
            const row = within(table).getAllByRole("row")[rowIndex + 1];
            const nameCell = within(row).getByRole("cell", { description: "Tag Name" });
            expect(nameCell).toHaveTextContent(tag.name);

            expect(result.getByText(tag.name)).toBeInTheDocument();
            if (tag.countDiscussions) {
                const usageCell = within(row).getByRole("cell", { description: "Usage" });
                expect(usageCell).toHaveTextContent(`${tag.countDiscussions}`);
            }
        });
    });

    it("displays the global scope", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
            await vitest.waitFor(() => expect(mockTagsApi.getTags).toHaveReturned());
        });

        table = (await result.findByRole("table")) as HTMLTableElement;

        const indexOfGlobalTag = mockTagsResponse.data.findIndex(
            (tag) => !tag.scope?.siteSectionIDs && !tag.scope?.allowedCategoryIDs,
        );
        const globalTagRow = within(table).getAllByRole("row")[indexOfGlobalTag + 1];
        const scopeCell = within(globalTagRow).getByRole("cell", { description: /scope/i });
        expect(scopeCell).toHaveTextContent("All");
    });

    it("displays the category scope", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
            await vitest.waitFor(() => expect(mockTagsApi.getTags).toHaveReturned());
        });

        table = (await result.findByRole("table")) as HTMLTableElement;

        const indexOfCategoryScopedTag = mockTagsResponse.data.findIndex(
            (tag) => tag.scope?.allowedCategoryIDs && tag.scope?.allowedCategoryIDs.length > 1,
        );
        const categoryScopedTag = mockTagsResponse.data[indexOfCategoryScopedTag];
        const categoryScopedTagRow = within(table).getAllByRole("row")[indexOfCategoryScopedTag + 1];
        const scopeCell = within(categoryScopedTagRow).getByRole("cell", { description: /scope/i });
        expect(scopeCell).toHaveTextContent(`${categoryScopedTag.scope!.allowedCategoryIDs!.length} Categories`);
    });

    it("displays the subcommunity scope", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
            await vitest.waitFor(() => expect(mockTagsApi.getTags).toHaveReturned());
        });

        table = (await result.findByRole("table")) as HTMLTableElement;

        const indexOfSubcommunityScopedTag = mockTagsResponse.data.findIndex(
            (tag) => tag.scope?.siteSectionIDs && tag.scope?.siteSectionIDs.length > 1,
        );
        const subcommunityScopedTag = mockTagsResponse.data[indexOfSubcommunityScopedTag];
        const subcommunityScopedTagRow = within(table).getAllByRole("row")[indexOfSubcommunityScopedTag + 1];
        const scopeCell = within(subcommunityScopedTagRow).getByRole("cell", { description: /scope/i });

        expect(scopeCell).toHaveTextContent(`${subcommunityScopedTag.scope!.siteSectionIDs!.length} subcommunities`);
    });

    it("allows searching for tags", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
            await vitest.waitFor(() => expect(mockTagsApi.getTags).toHaveReturned());
        });

        const searchForm = await result.findByRole("search");
        const searchInput = await within(searchForm).findByRole("textbox");

        await act(async () => {
            fireEvent.change(searchInput, { target: { value: "javascript" } });
        });

        await act(async () => {
            fireEvent.submit(searchForm);
        });

        expect(mockTagsApi.getTags).toHaveBeenLastCalledWith(
            expect.objectContaining({
                query: "javascript",
            }),
        );
    });

    it("handles sorting by tag name", async () => {
        await act(async () => {
            result = renderTaggingSettings(mockTagsApi);
        });

        const tagNameHeader = await result.findByRole("button", { name: "Tag Name" });
        expect(tagNameHeader).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(tagNameHeader);
        });

        expect(mockTagsApi.getTags).toHaveBeenLastCalledWith(
            expect.objectContaining({
                sort: "-name",
            }),
        );
    });

    it("handles API errors gracefully", async () => {
        const mockApiWithError = {
            ...createMockTagsApi(),
            getTags: vitest.fn().mockRejectedValue(new Error("API Error")),
        };

        await act(async () => {
            result = renderTaggingSettings(mockApiWithError);
        });

        await waitFor(() => {
            // Page should still render even if API call fails
            expect(result.getByText("Tagging")).toBeInTheDocument();
        });
    });
});
