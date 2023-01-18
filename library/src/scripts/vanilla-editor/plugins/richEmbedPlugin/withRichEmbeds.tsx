/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { insertRichFile } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichFile";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";
import { MyEditor } from "@library/vanilla-editor/typescript";
import { isFileImage } from "@vanilla/utils";

export function withRichEmbeds(editor: MyEditor) {
    const { insertData } = editor;

    /**
     * Handle pastes and drag/drops of images and files.
     */
    editor.insertData = (data: DataTransfer) => {
        const { files } = data;
        if (files && files.length > 0) {
            for (const file of files) {
                if (isFileImage(file)) {
                    insertRichImage(editor, file);
                } else {
                    insertRichFile(editor, file);
                }
            }
        } else {
            insertData(data);
        }
    };

    return editor;
}
