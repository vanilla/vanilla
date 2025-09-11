/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOptionsBump from "@library/features/discussions/DiscussionOptionsBump";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { vitest } from "vitest";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { makeMockDiscussionsApi } from "@vanilla/addon-vanilla/posts/__fixtures__/DiscussionsApi.fixture";
import { DiscussionsApiContext } from "@vanilla/addon-vanilla/posts/DiscussionsApi";

const mockDiscussion = DiscussionFixture.mockDiscussion;

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false, enabled: true } },
});

const onMutateSuccess = vitest.fn(async function () {});

const mockDiscussionsApi = makeMockDiscussionsApi();

async function renderInProvider() {
    return render(
        <ToastProvider>
            <QueryClientProvider client={queryClient}>
                <DiscussionsApiContext.Provider value={{ api: mockDiscussionsApi }}>
                    <DiscussionOptionsBump discussion={mockDiscussion} onSuccess={onMutateSuccess} />
                </DiscussionsApiContext.Provider>
            </QueryClientProvider>
        </ToastProvider>,
    );
}

afterEach(() => {
    queryClient.clear();
    vitest.resetAllMocks();
});

describe("DiscussionOptionsBump", () => {
    describe("Success", () => {
        beforeEach(async () => {
            mockDiscussionsApi.bump = vitest.fn(async (_discussionID) => {
                return mockDiscussion;
            });
            await renderInProvider();
            const button = await screen.findByText("Bump");
            await userEvent.click(button);
        });

        it("makes an API call to the bump endpoint", async () => {
            expect(mockDiscussionsApi.bump).toHaveBeenCalledWith(mockDiscussion.discussionID);
        });

        it("calls the onMutateSuccess callback", async () => {
            await vitest.waitFor(() => {
                expect(mockDiscussionsApi.bump).toHaveReturned();
            });
            expect(onMutateSuccess).toHaveBeenCalled();
        });

        it("displays a success message", async () => {
            const successMessage = await screen.findByText("Success", { exact: false });
            expect(successMessage).toBeInTheDocument();
        });
    });

    describe("Error", () => {
        const fakeErrorMessage = "Fake Error";

        beforeEach(async () => {
            mockDiscussionsApi.bump = vitest.fn(async (_discussionID) => {
                throw new Error(fakeErrorMessage);
            });
            await renderInProvider();
            const button = await screen.findByText("Bump");
            await userEvent.click(button);
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
