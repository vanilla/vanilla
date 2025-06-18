/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { CommentsBulkActionsProvider } from "./CommentsBulkActionsContext";
import { renderHook } from "@testing-library/react-hooks";
import { useSessionStorage } from "@vanilla/react-utils";
import { ToastContext } from "@library/features/toaster/ToastContext";
import { BulkAction } from "@library/bulkActions/BulkActions.types";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: true,
            retry: false,
            staleTime: Infinity,
        },
    },
});

const mockToastProps = {
    toasts: [],
    addToast: vitest.fn(),
    updateToast: vitest.fn(),
    removeToast: vitest.fn(),
    setIsInModal: vitest.fn(),
};

const mockAdditionalBulkAction = {
    action: "TestBulkAction" as BulkAction,
    permission: "community.moderate",
    notAllowedMessage: "You don't have required permission to TestBulkAction selected comments.",
    contentRenderer: () => <></>,
};

const mockCommentID = 4;

async function renderInProvider() {
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <QueryClientProvider client={queryClient}>
                    <ToastContext.Provider value={mockToastProps}>
                        <DiscussionFixture.CommentParentProvider>
                            <CommentsBulkActionsProvider
                                selectableCommentIDs={[mockCommentID]}
                                setSelectAllCommentsCheckbox={() => {}}
                            >
                                <></>
                            </CommentsBulkActionsProvider>
                        </DiscussionFixture.CommentParentProvider>
                    </ToastContext.Provider>
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
}

describe("Comments Bulk Actions", () => {
    CommentsBulkActionsProvider.registerBulkAction(mockAdditionalBulkAction);
    it("Toast with comments bulk buttons (including additional one)", async () => {
        renderHook(() =>
            useSessionStorage(
                `${UserFixture.adminAsCurrent.data?.userID}_checkedCommentIDs_discussion/${DiscussionFixture.mockDiscussion.discussionID}`,
                [mockCommentID],
            ),
        );
        await renderInProvider();

        expect(mockToastProps.addToast).toHaveBeenCalledTimes(1);
        const commentsBulkActionsToastData = mockToastProps.addToast.mock.calls[0][0].body.props;
        ["additionalBulkActions", "categoryID", "handleBulkDelete", "handleBulkSplit", "handleSelectionClear"].forEach(
            (property) => {
                expect(commentsBulkActionsToastData[property]).toBeTruthy();
            },
        );
        expect(commentsBulkActionsToastData["selectedIDs"][0]).toBe(mockCommentID);
        expect(commentsBulkActionsToastData["additionalBulkActions"][0]).toStrictEqual(mockAdditionalBulkAction);
    });
});
