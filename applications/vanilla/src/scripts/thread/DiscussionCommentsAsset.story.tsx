/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
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
    const fakeDiscussion = LayoutEditorPreviewData.discussion();
    return (
        <QueryClientProvider client={queryClient}>
            <DiscussionCommentsAsset
                comments={{ data: STORY_COMMENTS, paging: LayoutEditorPreviewData.paging(5) }}
                apiParams={{ discussionID: fakeDiscussion.discussionID, limit: 5, page: 1 }}
                discussion={fakeDiscussion}
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
        <CurrentUserContextProvider currentUser={STORY_COMMENTS[0].insertUser}>
            <StoryCommentList />
        </CurrentUserContextProvider>
    );
};
export const AdminView = () => {
    return (
        <PermissionsFixtures.AllPermissions>
            <StoryCommentList />
        </PermissionsFixtures.AllPermissions>
    );
};
