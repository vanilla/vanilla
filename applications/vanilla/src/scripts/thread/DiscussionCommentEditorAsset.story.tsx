/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionCommentEditorAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionCommentsEditor",
};

const StoryCommentEditor = () => {
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
            <DiscussionCommentEditorAsset discussionID={1} categoryID={1} />
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <StoryCommentEditor />;
};
