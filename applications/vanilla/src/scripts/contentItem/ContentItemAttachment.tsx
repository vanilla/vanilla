/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AttachmentLayout from "@library/features/discussions/integrations/components/AttachmentLayout";
import { useReadableIntegrationContext } from "@library/features/discussions/integrations/Integrations.context";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { ContentItemAttachmentService } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachments.service";
import { Icon } from "@vanilla/icons";
import React from "react";

export function ContentItemAttachment(props: { attachment: IAttachment; onSuccessfulRefresh?: () => Promise<void> }) {
    const { attachment } = props;

    const { state, status, sourceUrl, sourceID, dateUpdated, dateInserted, metadata, insertUser } = attachment;
    const integration = useReadableIntegrationContext();

    const title = integration?.title ?? "Unknown Integration";
    const externalIDLabel = integration?.externalIDLabel ?? "Unknown #";
    const logoIcon = integration?.logoIcon ?? "meta-external";
    const attachmentTypeIcon = integration?.attachmentTypeIcon;

    const dynamicMetas = ContentItemAttachmentService.additionalMetaItems
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
