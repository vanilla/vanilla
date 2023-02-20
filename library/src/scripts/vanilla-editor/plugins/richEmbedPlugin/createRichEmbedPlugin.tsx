/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RichEmbedElement } from "@library/vanilla-editor/plugins/richEmbedPlugin/elements/RichEmbedElement";
import { onKeyDownRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/onKeyDownRichEmbed";
import RichLinkToolbar from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkToolbar";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { withRichEmbeds } from "@library/vanilla-editor/plugins/richEmbedPlugin/withRichEmbeds";
import { MyValue } from "@library/vanilla-editor/typescript";
import { createPluginFactory, withProps } from "@udecode/plate-core";

export const createRichEmbedPlugin = createPluginFactory<any, MyValue>({
    key: "rich_embed",
    withOverrides: withRichEmbeds,
    plugins: [
        {
            key: ELEMENT_RICH_EMBED_CARD,
            isElement: true,
            isVoid: true,
            component: RichEmbedElement,
            handlers: {
                onKeyDown: onKeyDownRichEmbed,
            },
        },
        {
            key: ELEMENT_RICH_EMBED_INLINE,
            isElement: true,
            isInline: true,
            isVoid: true,
            component: withProps(RichEmbedElement, { isInline: true }),
            handlers: {
                onKeyDown: onKeyDownRichEmbed,
            },
        },
    ],
    renderAfterEditable: RichLinkToolbar,
});
