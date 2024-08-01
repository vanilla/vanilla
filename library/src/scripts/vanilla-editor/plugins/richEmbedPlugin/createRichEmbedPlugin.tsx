/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { deserializeRichEmbedHtml } from "@library/vanilla-editor/plugins/richEmbedPlugin/deserializeRichEmbedHtml";
import {
    LegacyEmojiImage,
    RichEmbedElement,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/elements/RichEmbedElement";
import { onKeyDownRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/onKeyDownRichEmbed";
import RichLinkToolbar from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkToolbar";
import {
    ELEMENT_LEGACY_EMOJI,
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { withRichEmbeds } from "@library/vanilla-editor/plugins/richEmbedPlugin/withRichEmbeds";
import { MyValue } from "@library/vanilla-editor/typescript";
import { createPluginFactory, withProps } from "@udecode/plate-common";

export const createRichEmbedPlugin = createPluginFactory<any, MyValue>({
    key: "rich_embed",
    withOverrides: withRichEmbeds,
    deserializeHtml: deserializeRichEmbedHtml,
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
        {
            key: ELEMENT_LEGACY_EMOJI,
            isElement: true,
            isInline: true,
            isVoid: true,
            component: LegacyEmojiImage,
        },
    ],
    renderAfterEditable: RichLinkToolbar,
});
