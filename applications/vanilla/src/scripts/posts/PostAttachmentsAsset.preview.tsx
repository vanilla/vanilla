/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import PostAttachmentsAsset from "@vanilla/addon-vanilla/posts/PostAttachmentsAsset";
import React from "react";

const discussion = LayoutEditorPreviewData.discussion();

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

export function PostAttachmentsAssetPreview(
    props: Omit<React.ComponentProps<typeof PostAttachmentsAsset>, "discussion">,
) {
    return (
        <LayoutWidget>
            <QueryClientProvider client={queryClient}>
                <PostAttachmentsAsset {...props} discussion={discussion} />
            </QueryClientProvider>
        </LayoutWidget>
    );
}
