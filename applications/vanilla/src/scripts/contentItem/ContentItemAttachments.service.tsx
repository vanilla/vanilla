/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IAttachment } from "@library/features/discussions/integrations/Integrations.types";

export type ContentItemAttachmentMetaItem = {
    component: React.ComponentType<{ attachment: IAttachment }>;
    shouldRender: (attachment?: IAttachment) => boolean;
};

export const ContentItemAttachmentService = {
    additionalMetaItems: [] as ContentItemAttachmentMetaItem[],
    registerMetaItem: (
        /**
         * The component to render
         *
         */
        component: ContentItemAttachmentMetaItem["component"],

        /**
         * shouldRender receives the attachment as an argument and should return a boolean.
         */
        shouldRender: ContentItemAttachmentMetaItem["shouldRender"],
    ) => {
        ContentItemAttachmentService.additionalMetaItems.push({
            component,
            shouldRender,
        });
    },
};
