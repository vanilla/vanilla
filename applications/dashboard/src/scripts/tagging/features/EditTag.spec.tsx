/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IPatchTagRequestBody, IPatchTagResponseBody, ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { RenderResult, act, fireEvent, render, waitFor, within } from "@testing-library/react";

import EditTag from "@dashboard/tagging/features/EditTag";
import { ITagsApi } from "@dashboard/tagging/Tags.api";
import { TagsApiContext } from "@dashboard/tagging/TaggingSettings.context";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { mockTagItems } from "@dashboard/tagging/TaggingSettings.fixtures";
import { vitest } from "vitest";

const createMockTagsApi = (): ITagsApi => ({
    getTags: vitest.fn(),
    postTag: vitest.fn(),
    deleteTag: vitest.fn(),
    patchTag: vitest.fn().mockImplementation(async (params: IPatchTagRequestBody): Promise<IPatchTagResponseBody> => {
        const existingTag = mockTagItems.find((tag) => tag.tagID === params.tagID);
        if (!existingTag) {
            throw new Error("Tag not found");
        }

        const updatedTag: IPatchTagResponseBody = {
            ...existingTag,
            name: params.name,
            urlcode: params.urlcode,
            urlCode: params.urlcode,
            scope: params.scope,
            scopeType: params.scopeType,
        };
        return updatedTag;
    }),
});

describe("EditTag", () => {
    let queryClient: QueryClient;
    let result: RenderResult;
    let mockTagsApi: ITagsApi;
    let mockOnSuccess: ReturnType<typeof vitest.fn>;
    let testTag: ITagItem;
    let editButton: HTMLButtonElement;
    let modal: HTMLDialogElement;
    let form: HTMLFormElement;
    let nameInput: HTMLInputElement;
    let urlSlugInput: HTMLInputElement;
    const updatedTagName = "updated tag name";
    const errorMessage = "Update failed";

    function renderEditTag(tag = testTag) {
        return render(
            <ToastProvider>
                <QueryClientProvider client={queryClient}>
                    <TagsApiContext.Provider value={{ api: mockTagsApi }}>
                        <EditTag tag={tag} onSuccess={mockOnSuccess} scopeEnabled={true} />
                    </TagsApiContext.Provider>
                </QueryClientProvider>
            </ToastProvider>,
        );
    }

    async function setUp() {
        result = renderEditTag();
        editButton = (await result.findByRole("button", { name: "Edit Tag" })) as HTMLButtonElement;
        await act(async () => {
            fireEvent.click(editButton);
        });
        modal = (await result.findByRole("dialog")) as HTMLDialogElement;
        await waitFor(async () => {
            expect(modal).toBeVisible();
        });
        form = await within(modal).findByRole("form");
        nameInput = within(form).getByLabelText(/tag name/i);
        urlSlugInput = within(form).getByLabelText(/url slug/i);
    }

    async function setUpAndUpdateName(tagName: string) {
        await setUp();
        await act(async () => {
            fireEvent.change(nameInput, { target: { value: tagName } });
        });
    }

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
        testTag = mockTagItems[1];
    });

    it("opens the edit tag modal when edit button is clicked", async () => {
        await setUp();
        expect(modal).toBeInTheDocument();
    });

    it("closes the modal when cancel button is clicked", async () => {
        await setUp();
        const cancelButton = within(modal).getByLabelText("Close");

        await act(async () => {
            fireEvent.click(cancelButton);
        });

        await waitFor(async () => {
            expect(modal).not.toBeVisible();
        });
    });

    it("contains a form", async () => {
        await setUp();
        expect(form).toBeInTheDocument();
    });

    it("has initial values that correspond to the tag", async () => {
        await setUp();
        expect(nameInput).toHaveValue(testTag.name);
        expect(urlSlugInput).toHaveValue(testTag.urlcode);
    });

    it("URL slug input is disabled", async () => {
        await setUp();
        expect(urlSlugInput).toBeDisabled();
    });

    it("Does not change the URL slug when name is updated", async () => {
        await setUpAndUpdateName(updatedTagName);
        expect(urlSlugInput).toHaveValue(testTag.urlcode);
    });

    it("Calls the patchTag API with the correct tagID", async () => {
        await setUpAndUpdateName(updatedTagName);
        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockTagsApi.patchTag).toHaveBeenCalledTimes(1);
        expect(mockTagsApi.patchTag).toHaveBeenLastCalledWith(
            expect.objectContaining({
                tagID: testTag.tagID,
            }),
        );
    });

    it("Calls the patchTag API with the updated values", async () => {
        await setUpAndUpdateName(updatedTagName);
        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockTagsApi.patchTag).toHaveBeenLastCalledWith(
            expect.objectContaining({
                name: updatedTagName,
            }),
        );
    });

    it("Calls the onSuccess callback", async () => {
        await setUpAndUpdateName(updatedTagName);
        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockOnSuccess).toHaveBeenCalledTimes(1);
    });

    it("Closes the modal", async () => {
        await setUpAndUpdateName(updatedTagName);
        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(modal).not.toBeVisible();
        });
        expect(modal).not.toBeInTheDocument();
    });

    it("Shows a success message", async () => {
        await setUpAndUpdateName(updatedTagName);
        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(result.getByText("Tag updated successfully", { exact: false })).toBeInTheDocument();
        });
    });

    it("Shows an error message", async () => {
        mockTagsApi.patchTag = vitest.fn().mockRejectedValue(new Error(errorMessage));
        await setUpAndUpdateName(updatedTagName);

        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(mockTagsApi.patchTag).toHaveReturned();
        });

        expect(await result.findByText(errorMessage, { exact: false })).toBeInTheDocument();
    });

    it("Does not call the onSuccess callback on error", async () => {
        mockTagsApi.patchTag = vitest.fn().mockRejectedValue(new Error(errorMessage));
        await setUpAndUpdateName(updatedTagName);

        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(mockTagsApi.patchTag).toHaveReturned();
        });

        expect(mockOnSuccess).not.toHaveBeenCalled();
    });

    it("Does not close the modal on error", async () => {
        mockTagsApi.patchTag = vitest.fn().mockRejectedValue(new Error(errorMessage));
        await setUpAndUpdateName(updatedTagName);

        await act(async () => {
            fireEvent.submit(form);
        });

        await waitFor(async () => {
            expect(mockTagsApi.patchTag).toHaveReturned();
        });

        expect(modal).toBeVisible();
    });
});
