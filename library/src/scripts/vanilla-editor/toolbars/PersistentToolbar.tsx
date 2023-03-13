/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import EditorUploadButton from "@library/editor/flyouts/EditorUploadButton";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import EmojiFlyout from "@library/editor/flyouts/EmojiFlyout";
import Permission from "@library/features/users/Permission";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { insertRichFile } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichFile";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { useMyPlateEditorState } from "@library/vanilla-editor/typescript";
import { focusEditor, select, useEventPlateId } from "@udecode/plate-headless";
import React, { RefObject, useCallback } from "react";
import { cx } from "@emotion/css";

interface IProps {
    className?: string;
    uploadEnabled: boolean;
    contentRef?: RefObject<HTMLUListElement>;
    flyoutsDirectionBelow?: boolean;
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
            {!isMobile && (
                <EmojiFlyout
                    renderAbove={!props.flyoutsDirectionBelow}
                    onInsertEmoji={insertEmoji}
                    onVisibilityChange={() => {}}
                />
            )}
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
                renderAbove={!props.flyoutsDirectionBelow}
                createEmbed={(url) => {
                    insertRichEmbed(editor, url, RichLinkAppearance.CARD);
                    focusEditor(editor);
                }}
                createIframe={(url, frameHeight, frameWidth) => {
                    insertRichEmbed(editor, url, RichLinkAppearance.CARD, "iframe", {
                        height: frameHeight,
                        width: frameWidth,
                    });
                    focusEditor(editor);
                }}
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
            {getExtraComponents().map((item, i) => {
                return <React.Fragment key={i}>{item}</React.Fragment>;
            })}
        </ul>
    );
}

const extraComponents: React.ReactNode[] = [];
function getExtraComponents() {
    return extraComponents;
}

PersistentToolbar.addExtraButton = (node: React.ReactNode) => {
    extraComponents.push(node);
};
