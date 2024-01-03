/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import EditorUploadButton from "@library/editor/flyouts/EditorUploadButton";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import EmojiFlyout from "@library/editor/flyouts/EmojiFlyout";
import Permission from "@library/features/users/Permission";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { insertRichFile } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichFile";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { ElementToolbar } from "@library/vanilla-editor/toolbars/ElementToolbar";
import { useMyPlateEditorState } from "@library/vanilla-editor/typescript";
import { focusEditor, selectEditor, useEventPlateId } from "@udecode/plate-common";
import React, { RefObject, useCallback } from "react";

interface IProps {
    className?: string;
    uploadEnabled: boolean;
    contentRef?: RefObject<HTMLUListElement>;
    flyoutsDirection?: "above" | "below";
    isMobile: boolean;
}

/**
 * Static toolbar under/over the editor.
 */
export function PersistentToolbar(props: IProps) {
    const { uploadEnabled, className, contentRef, flyoutsDirection = "above" } = props;
    const editor = useMyPlateEditorState(useEventPlateId());

    // focus the editor and place cursor at the end if a selection does not already exist
    const ensureEditorFocused = () => {
        if (!editor.selection) {
            selectEditor(editor, { edge: "end", focus: true });
        } else {
            focusEditor(editor);
        }
    };

    const insertEmoji = useCallback(
        function (emojiChar: string) {
            ensureEditorFocused();
            editor.insertText(emojiChar);
        },
        [editor],
    );

    return (
        <ul className={cx(menuBarClasses().menuItemsList, className)} {...(contentRef && { ref: contentRef })}>
            {!props.isMobile && (
                <EmojiFlyout
                    renderAbove={flyoutsDirection === "above"}
                    onInsertEmoji={insertEmoji}
                    onVisibilityChange={() => {}}
                />
            )}
            {props.isMobile && <ElementToolbar renderAbove={flyoutsDirection === "above"} />}
            {uploadEnabled && (
                <Permission permission="uploads.add">
                    <li>
                        <EditorUploadButton
                            disabled={false}
                            type="image"
                            legacyMode={false}
                            onUpload={(files) => {
                                ensureEditorFocused();
                                files.forEach((file) => {
                                    insertRichImage(editor, file);
                                });
                            }}
                        />
                    </li>
                </Permission>
            )}
            <EmbedFlyout
                renderAbove={flyoutsDirection === "above"}
                createEmbed={(url) => {
                    ensureEditorFocused();
                    insertRichEmbed(editor, url, RichLinkAppearance.CARD);
                }}
                createIframe={({ url, width, height }) => {
                    ensureEditorFocused();
                    insertRichEmbed(editor, url, RichLinkAppearance.CARD, "iframe", {
                        width,
                        height,
                    });
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
                                ensureEditorFocused();
                                files.forEach((file) => {
                                    insertRichFile(editor, file);
                                });
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
