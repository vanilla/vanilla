/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import DiscussionCommentEditorAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { mockAPI } from "@library/__tests__/utility";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
        },
    },
});

const renderInProvider = (props?: Partial<React.ComponentProps<typeof DiscussionCommentEditorAsset>>) => {
    render(
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            ...UserFixture.createMockUser({ userID: 1 }),
                            countUnreadNotifications: 0,
                            countUnreadConversations: 0,
                        },
                    },
                },
            }}
        >
            <QueryClientProvider client={queryClient}>
                <DiscussionCommentEditorAsset {...props} discussionID={0} categoryID={0} />
            </QueryClientProvider>
        </TestReduxProvider>,
    );
};

const mockAdapter = mockAPI();

describe("DiscussionCommentEditorAsset", () => {
    it("Draft button is disabled when the editor is empty", async () => {
        renderInProvider();
        waitFor(() => {
            const draftButton = screen.getByRole("button", { name: "Save draft" });
            expect(draftButton).toBeInTheDocument();
            expect(draftButton).toBeDisabled();
        });
    });
    it("Editor is populated with a draft when an initial draft", async () => {
        renderInProvider({
            draft: {
                dateUpdated: "1990-08-20T00:00:00Z",
                draftID: 1,
                body: '[{"type":"p","children":[{"text":"Vanilla is awesome!"}]}]',
                format: "rich2",
            },
        });
        waitFor(() => {
            const draftButton = screen.getByRole("button", { name: "Save draft" });
            expect(draftButton).toBeInTheDocument();
            expect(draftButton).toBeEnabled();
            expect(screen.getByText("Vanilla is awesome!")).toBeInTheDocument();
        });
    });
    it("Save draft button calls the /drafts endpoint", async () => {
        mockAdapter.onPatch("/drafts/1").reply(200, {});
        renderInProvider({
            draft: {
                dateUpdated: "1990-08-20T00:00:00Z",
                draftID: 1,
                body: '[{"type":"p","children":[{"text":"Vanilla is awesome!"}]}]',
                format: "rich2",
            },
        });
        waitFor(() => {
            const draftButton = screen.getByRole("button", { name: "Save draft" });
            expect(draftButton).toBeInTheDocument();
            fireEvent.click(draftButton);
            expect(mockAdapter.history.patch.length).toBe(1);
        });
    });
});
