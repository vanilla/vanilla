/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, act } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOptionsBump from "@library/features/discussions/DiscussionOptionsBump";
import { mockAPI } from "@library/__tests__/utility";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

const discussion = DiscussionFixture.mockDiscussion;
let mockApi: MockAdapter;
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

    return render(
        <ToastProvider>
            <QueryClientProvider client={queryClient}>
                <DiscussionOptionsBump discussion={discussion} onSuccess={onMutateSuccess} />
            </QueryClientProvider>
        </ToastProvider>,
    );
}

beforeEach(() => {
    mockApi = mockAPI();
    onMutateSuccess.mockReset();
});

describe("DiscussionOptionsBump", () => {
    describe("Success", () => {
        beforeEach(async () => {
            mockApi.onPatch(`/discussions/${discussion.discussionID}/bump`).replyOnce(200, discussion);

            await renderInProvider();

            const button = await screen.findByText("Bump");

            await userEvent.click(button);
        });

        it("makes an API call to the bump endpoint", async () => {
            expect(mockApi.history.patch.length).toBe(1);
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
            mockApi
                .onPatch(`/discussions/${discussion.discussionID}/bump`)
                .replyOnce(500, { message: fakeErrorMessage });

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
