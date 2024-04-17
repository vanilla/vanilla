/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import {
    AttachmentIntegrationsApiContextProvider,
    AttachmentIntegrationsContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionAttachmentsAsset from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import React from "react";
import { FAKE_API, FAKE_ATTACHMENT } from "@library/features/discussions/integrations/fixtures/Integrations.fixtures";
import { STORY_USER } from "@library/storybook/storyData";

export default {
    title: "Widgets/DiscussionAttachmentsAsset",
};

const StoryAttachments = () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
                staleTime: Infinity,
            },
        },
    });

    const discussion = LayoutEditorPreviewData.discussion();

    return (
        <QueryClientProvider client={queryClient}>
            <AttachmentIntegrationsApiContextProvider api={FAKE_API}>
                <AttachmentIntegrationsContextProvider>
                    <DiscussionAttachmentsAsset
                        discussion={{
                            ...discussion,
                            attachments: [
                                {
                                    ...FAKE_ATTACHMENT,
                                    recordType: "discussion",
                                    recordID: `${discussion.discussionID}`,
                                    insertUser: STORY_USER,
                                },
                            ],
                        }}
                    />
                </AttachmentIntegrationsContextProvider>
            </AttachmentIntegrationsApiContextProvider>
        </QueryClientProvider>
    );
};

export const FullWidth = () => {
    return (
        <div style={{ maxWidth: 850 }}>
            <StoryAttachments />
        </div>
    );
};

export const Small = () => {
    return (
        <div style={{ maxWidth: 320 }}>
            <StoryAttachments />
        </div>
    );
};
