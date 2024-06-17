/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    IntegrationContextProvider,
    useAttachmentIntegrations,
    useIntegrationContext,
    useRefreshStaleAttachments,
} from "@library/features/discussions/integrations/Integrations.context";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import AttachmentLayout from "@library/features/discussions/integrations/components/AttachmentLayout";
import { Icon } from "@vanilla/icons";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
}

export function DiscussionAttachmentsAsset(props: IProps) {
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
            await refreshStaleAttachments(attachments.map(({ attachmentID }) => attachmentID));
            await invalidateDiscussionQuery();
        }
    }, [attachments?.length]);

    useEffect(() => {
        refreshStaleDiscussionAttachments();
    }, [refreshStaleDiscussionAttachments]);

    return (
        <>
            {attachments?.map((attachment) => (
                <IntegrationContextProvider
                    key={attachment.attachmentID}
                    recordType={"discussion"}
                    recordID={discussion.discussionID}
                    attachmentType={attachment.attachmentType}
                >
                    <DiscussionAttachment
                        key={attachment.attachmentID}
                        attachment={attachment}
                        onSuccessfulRefresh={invalidateDiscussionQuery}
                    />
                </IntegrationContextProvider>
            ))}
        </>
    );
}

export function DiscussionAttachment(props: { attachment: IAttachment; onSuccessfulRefresh?: () => Promise<void> }) {
    const {
        attachment: { state, status, sourceUrl, sourceID, dateUpdated, dateInserted, metadata, insertUser },
    } = props;

    const integrations = useAttachmentIntegrations();

    const integration = integrations.find((i) => i.attachmentType === props.attachment.attachmentType);

    const title = integration?.title ?? "Unknown Integration";
    const externalIDLabel = integration?.externalIDLabel ?? "Unknown #";
    const logoIcon = integration?.logoIcon ?? "meta-external";

    return (
        <AttachmentLayout
            title={title}
            notice={state ?? status}
            url={sourceUrl}
            idLabel={externalIDLabel}
            icon={<Icon icon={logoIcon} height={60} width={60} />}
            id={sourceID ? `${sourceID}` : undefined}
            dateUpdated={dateUpdated ?? dateInserted}
            user={insertUser}
            metadata={metadata}
        />
    );
}

export default DiscussionAttachmentsAsset;
