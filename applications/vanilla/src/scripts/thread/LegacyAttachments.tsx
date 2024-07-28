/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    WriteableIntegrationContextProvider,
    ReadableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
} from "@library/features/discussions/integrations/Integrations.context";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";

interface IProps {
    attachments?: IAttachment[];
}

export function LegacyThreadAttachmentsAsset(props: IProps) {
    const { attachments } = props;

    if (!attachments || attachments.length === 0) {
        // Nothing to render.
        // Because this is mounted at the top level, we can't return an empty fragment.
        return <div></div>;
    }

    return (
        <div>
            {props.attachments?.map((attachment, i) => {
                return (
                    <ReadableIntegrationContextProvider
                        key={attachment.attachmentID}
                        attachmentType={attachment.attachmentType}
                    >
                        <DiscussionAttachment key={i} attachment={attachment} />
                    </ReadableIntegrationContextProvider>
                );
            })}
        </div>
    );
}

interface IFormProps {
    recordType: string;
    recordID: number;
    redirectTarget: string;
    isAuthor: boolean;
}

export function LegacyIntegrationsOptionsMenuItems(props: IFormProps) {
    return <LegacyIntegrationsOptionsMenuItemsImpl {...props} />;
}

function LegacyIntegrationsOptionsMenuItemsImpl(props: IFormProps) {
    const { isAuthor } = props;
    const writeableIntegrations = useWriteableAttachmentIntegrations()
        .filter((integration) => integration.recordTypes.includes(props.recordType))
        .filter(({ writeableContentScope }) => (writeableContentScope === "own" ? isAuthor : true));

    return writeableIntegrations.length > 0 ? (
        <>
            {writeableIntegrations.map((integration) => {
                return (
                    <WriteableIntegrationContextProvider
                        key={integration.attachmentType}
                        recordType={props.recordType}
                        attachmentType={integration.attachmentType}
                        recordID={props.recordID}
                    >
                        <IntegrationButtonAndModal
                            onSuccess={async () => {
                                window.location.href = props.redirectTarget;
                            }}
                        />
                    </WriteableIntegrationContextProvider>
                );
            })}
        </>
    ) : null;
}
