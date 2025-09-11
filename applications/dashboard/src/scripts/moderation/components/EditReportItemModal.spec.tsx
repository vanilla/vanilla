/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EditReportItemModal } from "@dashboard/moderation/components/EditReportItemModal";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, RenderResult, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { act } from "react-dom/test-utils";
import { vitest } from "vitest";
import { mockDiscussionContent, mockDiscussionReport } from "@dashboard/moderation/__fixtures__/EditReportItem.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const renderInProvider = (children: React.ReactNode) => {
    return render(
        <TestReduxProvider>
            <QueryClientProvider client={queryClient}>
                <PermissionsFixtures.AllPermissions>{children}</PermissionsFixtures.AllPermissions>
            </QueryClientProvider>
        </TestReduxProvider>,
    );
};

describe("EditReportItemModal", () => {
    beforeEach(() => {
        vitest.clearAllMocks();
    });

    const mockOnSubmit = vitest.fn();
    const mockOnClose = vitest.fn();

    let result: RenderResult;
    let dialog: HTMLElement;
    let form: HTMLFormElement;

    beforeEach(async () => {
        await act(async () => {
            result = renderInProvider(
                <EditReportItemModal
                    isVisible={true}
                    onSubmit={mockOnSubmit}
                    onClose={mockOnClose}
                    report={mockDiscussionReport}
                />,
            );
        });
        await vitest.dynamicImportSettled(); //wait for the editor to be loaded
        dialog = await result.findByRole("dialog");
    });

    describe("Form", () => {
        let titleInput: HTMLInputElement;
        let editorInput: HTMLElement;

        beforeEach(async () => {
            form = await within(dialog).findByRole("form");
            titleInput = await within(form).findByLabelText("Title", { exact: false });
            editorInput = await within(form).findByTestId("vanilla-editor");
        });

        it("Renders a form within a modal", () => {
            expect(form).toBeInTheDocument();
        });

        describe("Initial values", () => {
            it("Has a title field, with the correctinitial value", async () => {
                expect(titleInput).toBeInTheDocument();
                expect(titleInput).toHaveValue(mockDiscussionReport.recordName);
            });

            it("Has a body field, with the correct initial value", async () => {
                expect(editorInput).toBeInTheDocument();
                expect(await within(editorInput).findByText(mockDiscussionContent)).toBeInTheDocument();
            });
        });

        describe("Form submission", () => {
            const newTitle = "New Title";
            beforeEach(async () => {
                await act(async () => {
                    fireEvent.click(titleInput);
                    await userEvent.clear(titleInput);
                    await userEvent.type(titleInput, newTitle);
                    fireEvent.submit(form);
                });
            });

            it("Calls the onSubmit callback with the correct values, and closes the modal", async () => {
                expect(mockOnSubmit).toHaveBeenCalledWith(
                    expect.objectContaining({
                        name: newTitle,
                    }),
                );
            });

            it("calls the onClose callback", async () => {
                expect(mockOnClose).toHaveBeenCalled();
            });
        });
    });
});
