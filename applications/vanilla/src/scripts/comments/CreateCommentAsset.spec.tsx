/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, act } from "@testing-library/react";
import CreateCommentAsset from "@vanilla/addon-vanilla/comments/CreateCommentAsset";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { mockAPI } from "@library/__tests__/utility";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
        },
    },
});

const renderInProvider = async (props?: Partial<React.ComponentProps<typeof CreateCommentAsset>>) => {
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                <QueryClientProvider client={queryClient}>
                    <CreateCommentAsset {...props} />
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
    await vi.dynamicImportSettled();
};

const mockAdapter = mockAPI();

describe("CreateCommentAsset", () => {
    it.skip("Draft button is disabled when the editor is empty", async () => {
        await renderInProvider();
        const draftButton = screen.getByRole("button", { name: "Save Draft" });
        expect(draftButton).toBeInTheDocument();
        expect(draftButton).toBeDisabled();
    });
    // Need to work on this with the new caching setup along with the test below
    it.skip("Editor is populated with a draft when an initial draft", async () => {
        localStorage.setItem(`vanilla//commentDraftParentID-1`, "0");
        await act(async () => {
            await renderInProvider({});
        });
        const draftButton = screen.getByRole("button", { name: "Save Draft" });
        expect(draftButton).toBeInTheDocument();
        expect(draftButton).toBeEnabled();
        expect(screen.getByText("Vanilla is awesome!")).toBeInTheDocument();
    });
});
