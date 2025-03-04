/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import CreateCommentAsset from "@vanilla/addon-vanilla/comments/CreateCommentAsset";
import React from "react";

export default {
    title: "Widgets/DiscussionCommentsEditor",
};

type IProps = Partial<React.ComponentProps<typeof CreateCommentAsset>> & {};

const StoryCommentEditor = (props?: IProps) => {
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
            <CreateCommentAsset {...props} />
        </QueryClientProvider>
    );
};

export const Default = () => {
    return <StoryCommentEditor />;
};

export const WithDraft = () => {
    return <StoryCommentEditor />;
};
