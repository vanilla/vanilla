/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    ReadableIntegrationContextProvider,
    useRefreshStaleAttachments,
} from "@library/features/discussions/integrations/Integrations.context";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/comments/CommentThread.hooks";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { useCallback, useEffect } from "react";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    containerOptions?: IHomeWidgetContainerOptions;
    title?: string;
    description?: string;
    subtitle?: string;
}

export function PostAttachmentsAsset(props: IProps) {
    const { discussion: discussionPreload, discussionApiParams } = props;

    const { discussionID } = discussionPreload;

    const {
        query: { data },
        invalidate: invalidateDiscussionQuery,
    } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const discussion = data!;
    const attachments = discussion.attachments;

    const refreshStaleAttachments = useRefreshStaleAttachments();

    const refreshStaleDiscussionAttachments = useCallback(async () => {
        if (!!attachments && attachments.length > 0) {
            await refreshStaleAttachments(attachments);
            await invalidateDiscussionQuery();
        }
    }, [attachments?.length]);

    useEffect(() => {
        void refreshStaleDiscussionAttachments();
    }, [refreshStaleDiscussionAttachments]);

    if (!discussion.attachments || discussion.attachments.length === 0) {
        return <></>;
    }

    return (
        <PageBox
            options={{
                borderType: props.containerOptions?.borderType,
                background: props.containerOptions?.outerBackground,
            }}
        >
            <PageHeadingBox
                options={{ alignment: props.containerOptions?.headerAlignment }}
                title={props.title}
                description={props.description}
                subtitle={props.subtitle}
            />
            {attachments?.map((attachment) => (
                <ReadableIntegrationContextProvider
                    key={attachment.attachmentID}
                    attachmentType={attachment.attachmentType}
                >
                    <ContentItemAttachment
                        key={attachment.attachmentID}
                        attachment={attachment}
                        onSuccessfulRefresh={invalidateDiscussionQuery}
                    />
                </ReadableIntegrationContextProvider>
            ))}
        </PageBox>
    );
}

export default PostAttachmentsAsset;
