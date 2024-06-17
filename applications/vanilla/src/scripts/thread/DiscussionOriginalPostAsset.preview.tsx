/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof DiscussionOriginalPostAsset>, "comments" | "discussion"> {}

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
export function DiscussionOriginalPostAssetPreview(props: IProps) {
    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <DiscussionOriginalPostAsset
                    {...props}
                    category={LayoutEditorPreviewData.discussion().category!}
                    discussion={discussion}
                />
            </QueryClientProvider>
        </Widget>
    );
}
