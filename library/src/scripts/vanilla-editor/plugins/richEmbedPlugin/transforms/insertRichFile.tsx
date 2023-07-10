/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { ELEMENT_RICH_EMBED_CARD } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor, MyElement } from "@library/vanilla-editor/typescript";
import { insertFragment, insertNodes } from "@udecode/plate-headless";

export function insertRichFile(editor: MyEditor, file: File) {
    const currentEmbed = queryRichLink(editor);
    const insertFunc = currentEmbed ? insertNodes : insertFragment;
    insertFunc<MyElement>(editor, [
        {
            type: ELEMENT_RICH_EMBED_CARD,
            children: [{ text: "" }],
            dataSourceType: "file",
            uploadFile: file,
        },
    ]);
}
