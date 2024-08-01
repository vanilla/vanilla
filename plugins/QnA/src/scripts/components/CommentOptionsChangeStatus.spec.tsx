/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act, within } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import CommentOptionsChangeStatus from "./CommentOptionsChangeStatus";
import { QnAStatus } from "@dashboard/@types/api/comment";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";

const rejectedComment = {
    ...CommentFixture.mockComment,
    attributes: {
        answer: {
            status: QnAStatus.REJECTED,
        },
    },
};
const onSuccess = vitest.fn(async function () {});

async function renderInProvider() {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: true,
                retry: false,
            },
        },
    });

    return render(
        <ToastProvider>
            <QueryClientProvider client={queryClient}>
                <CommentOptionsChangeStatus comment={rejectedComment} onSuccess={onSuccess} />
            </QueryClientProvider>
        </ToastProvider>,
    );
}

async function clickOptionButtonToOpenForm() {
    const button = await screen.findByText("Change Status");
    await act(async () => {
        fireEvent.click(button);
    });
}

async function fillAndSubmitForm() {
    const form = await screen.findByRole("form");

    const acceptRadioButton = within(form).getByLabelText("Yes", {
        exact: false,
        selector: `input[value='${QnAStatus.ACCEPTED}']`,
    });
    await act(async () => {
        fireEvent.click(acceptRadioButton);
    });

    const submitButton = await within(form).findByText("Save");
    await act(async () => {
        fireEvent.click(submitButton);
    });
}

describe("CommentOptionsChangeStatus", () => {
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
        onSuccess.mockReset();
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("renders a form with the correct radio button initially selected", async () => {
        await act(async () => {
            renderInProvider();
        });
        await clickOptionButtonToOpenForm();
        const form = await screen.findByRole("form");
        expect(form).toBeInTheDocument();
        const rejectOption = within(form).getByLabelText("No", {
            exact: false,
            selector: `input[value='${QnAStatus.REJECTED}']`,
        });
        expect(rejectOption).toBeChecked();
    });

    describe("Successful form submission", () => {
        beforeEach(async () => {
            mockAdapter = mockAPI();
            onSuccess.mockReset();

            mockAdapter.onPatch(`/comments/${rejectedComment.commentID}/answer`).replyOnce(
                (requestConfig: {
                    data: {
                        status: QnAStatus;
                    };
                }) => {
                    return [
                        200,
                        {
                            ...rejectedComment,
                            attributes: {
                                answer: {
                                    status: requestConfig.data.status,
                                },
                            },
                        },
                    ];
                },
            );
            await act(async () => {
                renderInProvider();
            });
            await clickOptionButtonToOpenForm();
            await fillAndSubmitForm();
        });

        it("makes an API call to the patch endpoint", async () => {
            expect(mockAdapter.history.patch.length).toBe(1);
        });

        it("calls the onSuccess callback", async () => {
            expect(onSuccess).toHaveBeenCalledTimes(1);
        });
    });

    describe("Error in form submission", () => {
        const fakeErrorMessage = "Fake Error";

        beforeEach(async () => {
            mockAdapter
                .onPatch(`/comments/${rejectedComment.commentID}/answer`)
                .replyOnce(500, { message: fakeErrorMessage });

            await act(async () => {
                renderInProvider();
            });
            await clickOptionButtonToOpenForm();
            await fillAndSubmitForm();
        });

        it("does not call the onSuccess callback", async () => {
            expect(onSuccess).not.toHaveBeenCalled();
        });

        it("displays the error message", async () => {
            const errorMessage = await screen.findByText(fakeErrorMessage, { exact: false });
            expect(errorMessage).toBeInTheDocument();
        });
    });
});
