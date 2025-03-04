/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOptionsDismiss from "@library/features/discussions/DiscussionOptionsDismiss";
import { mockAPI } from "@library/__tests__/utility";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import type { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";

const discussion = {
    ...DiscussionFixture.mockDiscussion,
    pinned: true,
    dismissed: false,
};

const onMutateSuccess = vitest.fn(async function () {});

async function renderInProvider() {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: true,
                retry: false,
            },
        },
    });

    const result = render(
        <ToastProvider>
            <QueryClientProvider client={queryClient}>
                <DiscussionOptionsDismiss discussion={discussion} onSuccess={onMutateSuccess} />
            </QueryClientProvider>
        </ToastProvider>,
    );
    await vitest.dynamicImportSettled();
    return result;
}

let mockAdapter: MockAdapter;
beforeEach(() => {
    mockAdapter = mockAPI();
    onMutateSuccess.mockReset();
});

describe("DiscussionOptionsDismiss", () => {
    describe("Success", () => {
        beforeEach(async () => {
            mockAdapter
                .onPut(`/discussions/${discussion.discussionID}/dismiss`)
                .replyOnce((requestConfig: { data: DiscussionsApi.DismissParams }) => {
                    return [
                        200,
                        {
                            ...discussion,
                            dismissed: requestConfig.data.dismissed,
                        },
                    ];
                });

            await renderInProvider();

            const button = await screen.findByText("Dismiss", { exact: false });
            await act(async () => {
                fireEvent.click(button);
            });
        });

        it("makes an API call to the dismiss endpoint", async () => {
            expect(mockAdapter.history.put.length).toBe(1);
        });

        it("calls the onMutateSuccess callback", async () => {
            expect(onMutateSuccess).toHaveBeenCalledTimes(1);
        });

        it("displays a success message", async () => {
            const successMessage = await screen.findByText("Success", { exact: false });
            expect(successMessage).toBeInTheDocument();
        });
    });

    describe("Error", () => {
        const fakeErrorMessage = "Fake Error";

        beforeEach(async () => {
            mockAdapter
                .onPut(`/discussions/${discussion.discussionID}/dismiss`)
                .replyOnce(500, { message: fakeErrorMessage });

            await renderInProvider();
            const button = await screen.findByText("Dismiss", { exact: false });
            await act(async () => {
                fireEvent.click(button);
            });
        });

        it("does not call the onMutateSuccess callback", async () => {
            expect(onMutateSuccess).not.toHaveBeenCalled();
        });

        it("displays the error message", async () => {
            const errorMessage = await screen.findByText(fakeErrorMessage, { exact: false });
            expect(errorMessage).toBeInTheDocument();
        });
    });
});
