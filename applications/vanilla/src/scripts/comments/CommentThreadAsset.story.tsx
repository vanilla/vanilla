/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import type { IUserFragment } from "@library/@types/api/users";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_COMMENTS, STORY_ME_ADMIN } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import CommentThreadAsset from "@vanilla/addon-vanilla/comments/CommentThreadAsset";
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
            <CommentThreadAsset
                comments={{ data: STORY_COMMENTS, paging: LayoutEditorPreviewData.paging(5) }}
                threadStyle={"flat"}
                apiParams={{
                    parentRecordType: "discussion",
                    parentRecordID: fakeDiscussion.discussionID,
                    limit: 5,
                    page: 1,
                }}
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
        <CurrentUserContextProvider currentUser={{ ...STORY_ME_ADMIN, ...STORY_COMMENTS[0].insertUser }}>
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
