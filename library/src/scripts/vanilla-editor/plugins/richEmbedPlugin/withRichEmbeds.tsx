/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MyEditor } from "@library/vanilla-editor/typescript";
import { normalizeRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/normalizeRichEmbed";
import { insertRichEmbedData } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbedData";

export function withRichEmbeds(editor: MyEditor) {
    /**
     * Handle pastes and drag/drops of images and files.
     */
    editor.insertData = (data: DataTransfer) => insertRichEmbedData(editor, data);

    editor.normalizeNode = normalizeRichEmbed(editor);

    return editor;
}
