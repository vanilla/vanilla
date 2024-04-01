/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    AttachmentIntegrationsContextProvider,
    IntegrationContextProvider,
    useIntegrationContext,
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
    } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const discussion = data!;
    const attachments = discussion.attachments;

    return (
        <AttachmentIntegrationsContextProvider>
            {attachments?.map((attachment) => {
                return (
                    <IntegrationContextProvider
                        key={attachment.attachmentID}
                        recordType={"discussion"}
                        recordID={discussion.discussionID}
                        attachmentType={attachment.attachmentType}
                    >
                        <DiscussionAttachment attachment={attachment} />
                    </IntegrationContextProvider>
                );
            })}
        </AttachmentIntegrationsContextProvider>
    );
}

export function DiscussionAttachment(props: { attachment: IAttachment }) {
    const { title, externalIDLabel, logoIcon } = useIntegrationContext();
    const {
        attachment: { state, status, sourceUrl, sourceID, dateUpdated, dateInserted, metadata, insertUser },
    } = props;

    const details = metadata
        .map(({ labelCode, value }) => ({ label: labelCode, value: `${value ?? ""}` }))
        .filter((d) => !!d.value);

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
            details={details}
        />
    );
}

export default DiscussionAttachmentsAsset;
