/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useCallback, useEffect } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    ReadableIntegrationContextProvider,
    useRefreshStaleAttachments,
    useReadableIntegrationContext,
} from "@library/features/discussions/integrations/Integrations.context";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import AttachmentLayout from "@library/features/discussions/integrations/components/AttachmentLayout";
import { Icon } from "@vanilla/icons";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    containerOptions?: IHomeWidgetContainerOptions;
    title?: string;
    description?: string;
    subtitle?: string;
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
            await refreshStaleAttachments(attachments);
            await invalidateDiscussionQuery();
        }
    }, [attachments?.length]);

    useEffect(() => {
        refreshStaleDiscussionAttachments();
    }, [refreshStaleDiscussionAttachments]);

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
                    <DiscussionAttachment
                        key={attachment.attachmentID}
                        attachment={attachment}
                        onSuccessfulRefresh={invalidateDiscussionQuery}
                    />
                </ReadableIntegrationContextProvider>
            ))}
        </PageBox>
    );
}

export function DiscussionAttachment(props: { attachment: IAttachment; onSuccessfulRefresh?: () => Promise<void> }) {
    const { attachment } = props;

    const { state, status, sourceUrl, sourceID, dateUpdated, dateInserted, metadata, insertUser } = attachment;
    const integration = useReadableIntegrationContext();

    const title = integration?.title ?? "Unknown Integration";
    const externalIDLabel = integration?.externalIDLabel ?? "Unknown #";
    const logoIcon = integration?.logoIcon ?? "meta-external";
    const attachmentTypeIcon = integration?.attachmentTypeIcon;

    const dynamicMetas = DiscussionAttachment.additionalMetaItems
        .filter(({ shouldRender }) => shouldRender(attachment))
        .map(({ component: MetaItemComponent }, index) => <MetaItemComponent key={index} attachment={attachment} />);

    return (
        <AttachmentLayout
            title={title}
            notice={state ?? status}
            url={sourceUrl}
            idLabel={externalIDLabel}
            icon={<Icon icon={logoIcon} height={60} width={60} />}
            attachmentTypeIcon={attachmentTypeIcon ? <Icon icon={attachmentTypeIcon} /> : undefined}
            id={sourceID ? `${sourceID}` : undefined}
            dateUpdated={dateUpdated ?? dateInserted}
            user={insertUser}
            metadata={metadata}
            metas={dynamicMetas.length > 0 ? dynamicMetas : undefined}
        />
    );
}

export type DiscussionAttachmentLayoutMetaItem = {
    component: React.ComponentType<{ attachment: IAttachment }>;
    shouldRender: (attachment?: IAttachment) => boolean;
};

DiscussionAttachment.additionalMetaItems = [] as DiscussionAttachmentLayoutMetaItem[];

DiscussionAttachment.registerMetaItem = (
    /**
     * The component to render
     *
     */
    component: DiscussionAttachmentLayoutMetaItem["component"],

    /**
     * shouldRender receives the attachment as an argument and should return a boolean.
     */
    shouldRender: DiscussionAttachmentLayoutMetaItem["shouldRender"],
) => {
    DiscussionAttachment.additionalMetaItems.push({
        component,
        shouldRender,
    });
};

export default DiscussionAttachmentsAsset;
