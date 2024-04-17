/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { AttachmentIntegrationsContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { Widget } from "@library/layout/Widget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionAttachmentsAsset from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
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

export function DiscussionAttachmentsAssetPreview() {
    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <AttachmentIntegrationsContextProvider>
                    <DiscussionAttachmentsAsset discussion={discussion} />
                </AttachmentIntegrationsContextProvider>
            </QueryClientProvider>
        </Widget>
    );
}
