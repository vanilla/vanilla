/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, waitFor, within } from "@testing-library/react";

import DeleteTag from "@dashboard/tagging/features/DeleteTag";
import { IDeleteTagRequestBody } from "@dashboard/tagging/taggingSettings.types";
import { ITagsApi } from "@dashboard/tagging/Tags.api";
import { TagsApiContext } from "@dashboard/tagging/TaggingSettings.context";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { mockTagItems } from "@dashboard/tagging/TaggingSettings.fixtures";
import userEvent from "@testing-library/user-event";
import { vitest } from "vitest";

const createMockTagsApi = (): ITagsApi => ({
    getTags: vitest.fn(),
    postTag: vitest.fn(),
    patchTag: vitest.fn(),
    deleteTag: vitest.fn().mockImplementation(async (params: IDeleteTagRequestBody): Promise<void> => {
        const existingTag = mockTagItems.find((tag) => tag.tagID === params.tagID);
        if (!existingTag) {
            throw new Error("Tag not found");
        }
        // Mock successful deletion - no return value needed
    }),
});

describe("DeleteTag", () => {
    let queryClient: QueryClient;
    let result: RenderResult;
    let mockOnSuccess: ReturnType<typeof vitest.fn>;
    let mockTagsApi: ITagsApi;
    let confirmModal: HTMLDialogElement;
    let deleteButton: HTMLButtonElement;
    let dialogDeleteButton: HTMLButtonElement;
    let cancelButton: HTMLButtonElement;
    const testTag = mockTagItems[0]; // Use first tag from fixtures for testing
    const errorMessage = "Deletion failed";

    function renderDeleteTag(api: ITagsApi, tag = testTag) {
        return render(
            <ToastProvider>
                <QueryClientProvider client={queryClient}>
                    <TagsApiContext.Provider value={{ api }}>
                        <DeleteTag tag={tag} onSuccess={mockOnSuccess} />
                    </TagsApiContext.Provider>
                </QueryClientProvider>
            </ToastProvider>,
        );
    }

    beforeEach(() => {
        vitest.clearAllMocks();
        queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        mockOnSuccess = vitest.fn();
        mockTagsApi = createMockTagsApi();
    });

    it("Opens a the confirmation dialog", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        expect(confirmModal).toBeInTheDocument();
        const modalTitle = await within(confirmModal).findByRole("heading");
        expect(modalTitle).toHaveTextContent("Delete Tag");
    });

    it("contains Delete and Cancel buttons", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        dialogDeleteButton = (await within(confirmModal).findByRole("button", {
            name: "Delete",
        })) as HTMLButtonElement;
        cancelButton = (await within(confirmModal).findByRole("button", {
            name: "Cancel",
        })) as HTMLButtonElement;

        expect(dialogDeleteButton).toBeInTheDocument();
        expect(cancelButton).toBeInTheDocument();
    });

    it("hides the modal", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        cancelButton = (await within(confirmModal).findByRole("button", {
            name: "Cancel",
        })) as HTMLButtonElement;

        await act(async () => {
            await userEvent.click(cancelButton);
        });

        await waitFor(async () => {
            expect(confirmModal).not.toBeVisible();
        });
    });

    it("Calls the deleteTag API endpoint with the correct tagID", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        dialogDeleteButton = (await within(confirmModal).findByRole("button", {
            name: "Delete",
        })) as HTMLButtonElement;

        await act(async () => {
            await userEvent.click(dialogDeleteButton);
        });

        expect(mockTagsApi.deleteTag).toHaveBeenCalledWith({ tagID: testTag.tagID });
    });

    it("shows a success message", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        dialogDeleteButton = (await within(confirmModal).findByRole("button", {
            name: "Delete",
        })) as HTMLButtonElement;

        await act(async () => {
            await userEvent.click(dialogDeleteButton);
        });

        await waitFor(async () => {
            expect(result.getByText("successfully deleted tag", { exact: false })).toBeInTheDocument();
        });
    });

    it("closes the modal", async () => {
        result = renderDeleteTag(mockTagsApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });

        dialogDeleteButton = (await within(confirmModal).findByRole("button", {
            name: "Delete",
        })) as HTMLButtonElement;

        await act(async () => {
            await userEvent.click(dialogDeleteButton);
        });

        await waitFor(async () => {
            expect(confirmModal).not.toBeVisible();
        });
    });

    it("shows an error message", async () => {
        const errorMockApi = {
            ...createMockTagsApi(),
            deleteTag: vitest.fn().mockRejectedValue(new Error(errorMessage)),
        };

        result = renderDeleteTag(errorMockApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });
        const confirmDeleteButton = within(confirmModal).getByRole("button", { name: "Delete" });
        await act(async () => {
            fireEvent.click(confirmDeleteButton);
        });

        await waitFor(async () => {
            expect(result.getByText(errorMessage, { exact: false })).toBeInTheDocument();
        });
    });

    it("does not call onSuccess when deletion fails", async () => {
        const errorMockApi = {
            ...createMockTagsApi(),
            deleteTag: vitest.fn().mockRejectedValue(new Error(errorMessage)),
        };

        result = renderDeleteTag(errorMockApi);
        deleteButton = (await result.findByLabelText("Delete Tag")) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(deleteButton);
        });
        confirmModal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(confirmModal).toBeVisible();
        });
        const confirmDeleteButton = within(confirmModal).getByRole("button", { name: "Delete" });
        await act(async () => {
            fireEvent.click(confirmDeleteButton);
        });

        expect(mockOnSuccess).not.toHaveBeenCalled();
    });
});
