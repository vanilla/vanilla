/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOptionsDismiss from "@library/features/discussions/DiscussionOptionsDismiss";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { vitest } from "vitest";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { DiscussionsApiContext } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { makeMockDiscussionsApi } from "@vanilla/addon-vanilla/posts/__fixtures__/DiscussionsApi.fixture";

const mockDiscussion = {
    ...DiscussionFixture.mockDiscussion,
    pinned: true,
    dismissed: false,
};

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false, enabled: true } },
});

const onMutateSuccess = vitest.fn(async function () {});

const mockDiscussionsApi = makeMockDiscussionsApi();

async function renderInProvider() {
    const result = render(
        <ToastProvider>
            <QueryClientProvider client={queryClient}>
                <DiscussionsApiContext.Provider value={{ api: mockDiscussionsApi }}>
                    <DiscussionOptionsDismiss discussion={mockDiscussion} onSuccess={onMutateSuccess} />
                </DiscussionsApiContext.Provider>
            </QueryClientProvider>
        </ToastProvider>,
    );
    await vitest.dynamicImportSettled();
    return result;
}

afterEach(() => {
    queryClient.clear();
    vitest.resetAllMocks();
});

describe("DiscussionOptionsDismiss", () => {
    describe("Success", () => {
        beforeEach(async () => {
            mockDiscussionsApi.dismiss = vitest.fn(async (_discussionID, apiParams) => {
                return {
                    dismissed: !!apiParams.dismissed,
                };
            });
            await renderInProvider();
            const button = await screen.findByText("Dismiss", { exact: false });
            await act(async () => {
                fireEvent.click(button);
            });
        });

        it("makes an API call to the dismiss endpoint", async () => {
            expect(mockDiscussionsApi.dismiss).toHaveBeenCalledWith(
                mockDiscussion.discussionID,
                expect.objectContaining({
                    dismissed: true,
                }),
            );
        });

        it("calls the onMutateSuccess callback", async () => {
            await vitest.waitFor(() => {
                expect(mockDiscussionsApi.dismiss).toHaveReturned();
            });
            expect(onMutateSuccess).toHaveBeenCalled;
        });

        it("displays a success message", async () => {
            const successMessage = await screen.findByText("Success", { exact: false });
            expect(successMessage).toBeInTheDocument();
        });
    });

    describe("Error", () => {
        const fakeErrorMessage = "Fake Error";

        beforeEach(async () => {
            mockDiscussionsApi.dismiss = vitest.fn(async () => {
                throw new Error(fakeErrorMessage);
            });
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
