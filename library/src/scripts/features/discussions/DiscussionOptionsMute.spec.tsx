/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DiscussionOptionsMute } from "@library/features/discussions/DiscussionOptionsMute";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { makeMockDiscussionsApi } from "@vanilla/addon-vanilla/posts/__fixtures__/DiscussionsApi.fixture";
import { DiscussionsApiContext } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { vitest } from "vitest";

const mockDiscussion = {
    ...DiscussionFixture.mockDiscussion,
    muted: false,
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
                    <DiscussionOptionsMute discussion={mockDiscussion} onSuccess={onMutateSuccess} />
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

describe("DiscussionOptionsMute", () => {
    describe("Success", () => {
        beforeEach(async () => {
            mockDiscussionsApi.mute = vitest.fn(async (_discussionID, apiParams) => {
                return {
                    muted: !!apiParams.muted,
                };
            });
            await renderInProvider();
            const button = await screen.findByText("Mute", { exact: false });
            await userEvent.click(button);
        });

        it("makes an API call to the mute endpoint", async () => {
            expect(mockDiscussionsApi.mute).toHaveBeenCalledWith(
                mockDiscussion.discussionID,
                expect.objectContaining({
                    muted: true,
                }),
            );
        });

        it("calls the onMutateSuccess callback", async () => {
            await vitest.waitFor(() => {
                expect(mockDiscussionsApi.mute).toHaveReturned();
            });
            expect(onMutateSuccess).toHaveBeenCalledWith(true);
        });

        it("displays a success message", async () => {
            const successMessage = await screen.findByText("Discussion has been muted", { exact: false });
            expect(successMessage).toBeInTheDocument();
        });
    });

    describe("Error", () => {
        const fakeErrorMessage = "Fake Error";

        beforeEach(async () => {
            mockDiscussionsApi.mute = vitest.fn(async () => {
                throw new Error(fakeErrorMessage);
            });
            await renderInProvider();
            const button = await screen.findByText("Mute", { exact: false });
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
