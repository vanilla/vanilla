/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyEditor } from "@library/vanilla-editor/typescript";
import { isFileImage } from "@vanilla/utils";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";
import { insertRichFile } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichFile";

export function insertRichEmbedData(editor: MyEditor, data: DataTransfer) {
    const { insertData } = editor;

    const { files, types } = data;
    if (files && files.length > 0 && !types.includes("text/plain")) {
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
}
