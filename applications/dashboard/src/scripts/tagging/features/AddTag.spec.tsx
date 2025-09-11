/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IPostTagRequestBody, IPostTagResponseBody } from "@dashboard/tagging/taggingSettings.types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, waitFor, within } from "@testing-library/react";

import AddTag from "@dashboard/tagging/features/AddTag";
import { ITagsApi } from "@dashboard/tagging/Tags.api";
import { TagsApiContext } from "@dashboard/tagging/TaggingSettings.context";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import userEvent from "@testing-library/user-event";
import { vitest } from "vitest";

const createMockTagsApi = (): ITagsApi => ({
    getTags: vitest.fn(),
    patchTag: vitest.fn(),
    deleteTag: vitest.fn(),
    postTag: vitest.fn().mockImplementation(async (params: IPostTagRequestBody): Promise<IPostTagResponseBody> => {
        const newTag: IPostTagResponseBody = {
            tagID: Date.now(), // Use timestamp as unique ID for testing
            name: params.name,
            urlcode: params.urlcode,
            urlCode: params.urlcode,
            type: "user",
            countDiscussions: 0,
            dateInserted: new Date().toISOString(),
            scope: params.scope,
            scopeType: params.scopeType,
        };
        return newTag;
    }),
});

describe("AddTag", () => {
    let queryClient: QueryClient;
    let result: RenderResult;
    let mockTagsApi: ITagsApi;
    let mockOnSuccess: ReturnType<typeof vitest.fn>;
    let addButton: HTMLButtonElement;
    let modal: HTMLDialogElement;
    let form: HTMLFormElement;
    let nameInput: HTMLInputElement;
    let urlSlugInput: HTMLInputElement;

    beforeEach(() => {
        queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        mockTagsApi = createMockTagsApi();
        mockOnSuccess = vitest.fn();
    });

    function renderAddTag() {
        return render(
            <ToastProvider>
                <QueryClientProvider client={queryClient}>
                    <TagsApiContext.Provider value={{ api: mockTagsApi }}>
                        <AddTag onSuccess={mockOnSuccess} scopeEnabled={true} />
                    </TagsApiContext.Provider>
                </QueryClientProvider>
            </ToastProvider>,
        );
    }

    it("opens the add tag modal when Add Tag button is clicked", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        // Open modal
        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        expect(modal).toBeInTheDocument();
    });

    it("contains a form", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        // Open modal
        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        const form = await within(modal).findByRole("form");
        expect(form).toBeInTheDocument();
    });

    it("closes the modal when cancel button is clicked", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        // Open modal
        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        const cancelButton = within(modal).getByLabelText("Close");

        await act(async () => {
            fireEvent.click(cancelButton);
        });

        await waitFor(async () => {
            expect(modal).not.toBeVisible();
        });
    });

    it("automatically generates a URL slug from the tag name", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
        });

        expect(urlSlugInput).toHaveValue("new-test-tag");
    });

    it("allows the user to edit the URL slug", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
        });

        await userEvent.click(urlSlugInput);
        await userEvent.clear(urlSlugInput);
        await userEvent.keyboard("new-test-tag-2");
        await userEvent.tab();
        expect(urlSlugInput).toHaveValue("new-test-tag-2");
    });

    it("submits the form with the correct data", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
            await userEvent.click(urlSlugInput);
            await userEvent.clear(urlSlugInput);
            await userEvent.keyboard("new-test-tag-2");
            await userEvent.tab();
        });

        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockTagsApi.postTag).toHaveBeenCalledWith(
            expect.objectContaining({
                name: "new test tag",
                urlcode: "new-test-tag-2",
                scopeType: "global",
            }),
        );
    });

    it("calls the onSuccess callback", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
            await userEvent.click(urlSlugInput);
            await userEvent.clear(urlSlugInput);
            await userEvent.keyboard("new-test-tag-2");
            await userEvent.tab();
        });

        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockOnSuccess).toHaveBeenCalled();
    });

    it("shows a success toast", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
            await userEvent.click(urlSlugInput);
            await userEvent.clear(urlSlugInput);
            await userEvent.keyboard("new-test-tag-2");
            await userEvent.tab();
        });

        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(result.getByText("Tag added successfully")).toBeInTheDocument();
        });
    });

    it("closes the modal", async () => {
        result = renderAddTag();
        addButton = (await result.findByRole("button", { name: "Add Tag" })) as HTMLButtonElement;

        await act(async () => {
            fireEvent.click(addButton);
        });

        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });

        form = within(modal).getByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);

        await act(async () => {
            await userEvent.click(nameInput);
            await userEvent.keyboard("new test tag");
            await userEvent.tab();
            await userEvent.click(urlSlugInput);
            await userEvent.clear(urlSlugInput);
            await userEvent.keyboard("new-test-tag-2");
            await userEvent.tab();
        });

        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(modal).not.toBeVisible();
        });
    });
});
