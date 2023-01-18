/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
    RichLinkAppearance,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor, MyElement } from "@library/vanilla-editor/typescript";
import { insertFragment, insertNodes } from "@udecode/plate-headless";

export function insertRichEmbed(
    editor: MyEditor,
    url: string,
    appearance: RichLinkAppearance.CARD | RichLinkAppearance.INLINE,
) {
    const currentEmbed = queryRichLink(editor);
    const insertFunc = currentEmbed ? insertNodes : insertFragment;
    insertFunc<MyElement>(editor, [
        {
            type: appearance === RichLinkAppearance.CARD ? ELEMENT_RICH_EMBED_CARD : ELEMENT_RICH_EMBED_INLINE,
            children: [{ text: "" }],
            dataSourceType: "url",
            url,
        },
    ]);
}
