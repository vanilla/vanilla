/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { STORY_DISCUSSION } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionOriginalPost",
};

interface IProps extends Partial<React.ComponentProps<typeof DiscussionOriginalPostAsset>> {}

const StoryDiscussionCommentEditorAsset = (props?: IProps) => {
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
            <DiscussionOriginalPostAsset
                category={STORY_DISCUSSION.category}
                discussion={STORY_DISCUSSION}
                {...props}
            />
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <StoryDiscussionCommentEditorAsset />;
};

export const CurrentUser = () => {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            ...STORY_DISCUSSION.insertUser,
                            countUnreadNotifications: 0,
                            countUnreadConversations: 0,
                        },
                    },
                },
            }}
        >
            <StoryDiscussionCommentEditorAsset />
        </TestReduxProvider>
    );
};
