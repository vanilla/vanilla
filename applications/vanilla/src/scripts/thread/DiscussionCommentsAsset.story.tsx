/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LoadStatus } from "@library/@types/api/core";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { STORY_COMMENTS } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionCommentsList",
};

const StoryCommentList = () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
                staleTime: Infinity,
            },
        },
    });
    return (
        <QueryClientProvider client={queryClient}>
            <DiscussionCommentsAsset
                commentsPreload={{ data: STORY_COMMENTS, paging: LayoutEditorPreviewData.paging(5) }}
                apiParams={{ discussionID: "fake", limit: 5, page: 1 }}
                discussion={{ ...LayoutEditorPreviewData.discussion() }}
            />
        </QueryClientProvider>
    );
};

export const GuestView = () => {
    return (
        <PermissionsFixtures.NoPermissions>
            <StoryCommentList />
        </PermissionsFixtures.NoPermissions>
    );
};

export const MemberView = () => {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            ...STORY_COMMENTS[0].insertUser,
                            countUnreadNotifications: 0,
                            countUnreadConversations: 0,
                        },
                    },
                },
            }}
        >
            <StoryCommentList />
        </TestReduxProvider>
    );
};
export const AdminView = () => {
    return (
        <PermissionsFixtures.AllPermissions>
            <StoryCommentList />
        </PermissionsFixtures.AllPermissions>
    );
};
