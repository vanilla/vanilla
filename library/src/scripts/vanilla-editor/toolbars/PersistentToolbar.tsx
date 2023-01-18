import EditorUploadButton from "@library/editor/flyouts/EditorUploadButton";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import EmojiFlyout from "@library/editor/flyouts/EmojiFlyout";
import Permission from "@library/features/users/Permission";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { insertRichFile } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichFile";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { useMyPlateEditorState } from "@library/vanilla-editor/typescript";
import { focusEditor, insertTable, select, useEventPlateId } from "@udecode/plate-headless";
import React, { RefObject, useCallback } from "react";
import { cx } from "@emotion/css";

interface IProps {
    className?: string;
    uploadEnabled: boolean;
    contentRef?: RefObject<HTMLUListElement>;
}

/**
 * Static toolbar under/over the editor.
 */
export function PersistentToolbar(props: IProps) {
    const { uploadEnabled, className, contentRef } = props;
    const editor = useMyPlateEditorState(useEventPlateId());

    const insertEmoji = useCallback(
        function (emojiChar: string) {
            if (!editor.selection) {
                select(editor, { offset: 0, path: [0, 0] });
                focusEditor(editor);
            }
            editor.insertText(emojiChar);
            focusEditor(editor);
        },
        [editor],
    );

    const isMobile = false; //fixme

    return (
        <ul className={cx(menuBarClasses().menuItemsList, className)} {...(contentRef && { ref: contentRef })}>
            {!isMobile && <EmojiFlyout renderAbove onInsertEmoji={insertEmoji} onVisibilityChange={() => {}} />}
            {uploadEnabled && (
                <Permission permission="uploads.add">
                    <li>
                        <EditorUploadButton
                            disabled={false}
                            type="image"
                            legacyMode={false}
                            onUpload={(files) => {
                                files.forEach((file) => {
                                    insertRichImage(editor, file);
                                });
                                focusEditor(editor);
                            }}
                        />
                    </li>
                </Permission>
            )}
            <EmbedFlyout
                renderAbove
                createEmbed={(url) => {
                    insertRichEmbed(editor, url, RichLinkAppearance.CARD);
                    focusEditor(editor);
                }}
                createIframe={(url, frameHeight, frameWidth) => {}}
            />
            {uploadEnabled && (
                <Permission permission="uploads.add">
                    <li>
                        <EditorUploadButton
                            disabled={false}
                            type="file"
                            legacyMode={false}
                            onUpload={(files) => {
                                files.forEach((file) => {
                                    insertRichFile(editor, file);
                                });
                                focusEditor(editor);
                            }}
                        />
                    </li>
                </Permission>
            )}

            <li>
                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => {
                        if (!editor) {
                            return;
                        }
                        insertTable(editor, {
                            rowCount: 4,
                            colCount: 4,
                        });
                    }}
                >
                    Insert Table
                </Button>
            </li>
        </ul>
    );
}
