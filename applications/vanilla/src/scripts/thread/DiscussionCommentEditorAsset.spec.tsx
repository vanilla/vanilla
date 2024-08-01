/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, act } from "@testing-library/react";
import DiscussionCommentEditorAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
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

const renderInProvider = async (props?: Partial<React.ComponentProps<typeof DiscussionCommentEditorAsset>>) => {
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                <QueryClientProvider client={queryClient}>
                    <DiscussionCommentEditorAsset {...props} discussionID={0} categoryID={0} />
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
    await vi.dynamicImportSettled();
};

const mockAdapter = mockAPI();

describe("DiscussionCommentEditorAsset", () => {
    it("Draft button is disabled when the editor is empty", async () => {
        await renderInProvider();
        const draftButton = screen.getByRole("button", { name: "Save Draft" });
        expect(draftButton).toBeInTheDocument();
        expect(draftButton).toBeDisabled();
    });
    it("Editor is populated with a draft when an initial draft", async () => {
        await renderInProvider({
            draft: {
                dateUpdated: "1990-08-20T00:00:00Z",
                draftID: 1,
                body: '[{"type":"p","children":[{"text":"Vanilla is awesome!"}]}]',
                format: "rich2",
            },
        });
        const draftButton = screen.getByRole("button", { name: "Save Draft" });
        expect(draftButton).toBeInTheDocument();
        expect(draftButton).toBeEnabled();
        expect(screen.getByText("Vanilla is awesome!")).toBeInTheDocument();
    });
    // Not working following vite migration
    it.skip("Save draft button calls the /drafts endpoint", async () => {
        mockAdapter.onPatch("/drafts/1").reply(200, {});
        await renderInProvider({
            draft: {
                dateUpdated: "1990-08-20T00:00:00Z",
                draftID: 1,
                body: '[{"type":"p","children":[{"text":"Vanilla is awesome!"}]}]',
                format: "rich2",
            },
        });
        const draftButton = screen.getByRole("button", { name: "Save Draft" });

        await act(async () => {
            fireEvent.click(draftButton);
        });

        act(() => {
            fireEvent.click(draftButton);
        });
        expect(mockAdapter.history.patch.length).toBe(1);
    });
});
