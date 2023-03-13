/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useMemo } from "react";
import classNames from "classnames";
import { getMeta, t } from "@library/utility/appUtils";
import Permission from "@library/features/users/Permission";
import EditorUploadButton from "@library/editor/flyouts/EditorUploadButton";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import ParagraphMenusBarToggle from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { useEditor } from "@rich-editor/editor/context";
import EmojiFlyout from "@library/editor/flyouts/EmojiFlyout";

import Quill from "quill/core";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import { isFileImage } from "@vanilla/utils";

interface IProps {
    className?: string;
    contentRef?: React.RefObject<HTMLDivElement>;
    uploadEnabled?: boolean;
}

export function EditorEmbedBar(props: IProps) {
    const { isMobile, isLoading, legacyMode, editor } = useEditor();
    const embedModule: EmbedInsertionModule = useMemo(() => editor && editor.getModule("embed/insertion"), [editor]);

    const insertEmoji = useCallback(
        (emojiChar: string) => {
            if (editor) {
                const range = editor.getSelection(true);
                editor.insertEmbed(
                    range.index,
                    "emoji",
                    {
                        emojiChar,
                    },
                    Quill.sources.USER,
                );
                editor.setSelection(range.index + 1, 0, Quill.sources.SILENT);
            }
        },
        [editor],
    );

    if (!editor) {
        return null;
    }
    const uploadEnabled = props.uploadEnabled ?? true;
    const classesRichEditor = richEditorClasses(legacyMode);

    const createUploadHandler = (type: "image" | "file") => (files: File[]) => {
        const embedInsertion = editor.getModule("embed/insertion") as EmbedInsertionModule;
        const maxUploads = getMeta("upload.maxUploads", 20);
        // Increment the upload count to reset the input.
        const filesArray = Array.from(files);
        if (filesArray.length >= maxUploads) {
            const error = new Error(`Can't upload more than ${maxUploads} files at once.`);
            embedInsertion.createErrorEmbed(error);
            throw error;
        }

        filesArray.forEach((file) => {
            if (type === "image") {
                embedInsertion.createImageEmbed(file);
            } else {
                embedInsertion.createFileEmbed(file);
            }
        });
    };

    return (
        <div ref={props.contentRef} className={classNames(classesRichEditor.embedBar, props.className)}>
            <ul
                className={classNames("richEditor-menuItems", classesRichEditor.menuItems, "widthPadding")}
                role="menubar"
                aria-label={t("Inline Level Formatting Menu")}
            >
                {isMobile && (
                    <li className={classesRichEditor.menuItem} role="menuitem">
                        <ParagraphMenusBarToggle renderAbove={legacyMode} disabled={isLoading} mobile={true} />
                    </li>
                )}
                {!isMobile && (
                    <li
                        className={classNames("u-richEditorHiddenOnMobile", classesRichEditor.menuItem)}
                        role="menuitem"
                    >
                        <EmojiFlyout
                            disabled={isLoading}
                            renderAbove={legacyMode}
                            onInsertEmoji={insertEmoji}
                            legacyMode={legacyMode}
                            onVisibilityChange={forceSelectionUpdate}
                        />
                    </li>
                )}
                {uploadEnabled && (
                    <Permission permission="uploads.add">
                        <li className={classesRichEditor.menuItem} role="menuitem">
                            <EditorUploadButton
                                disabled={isLoading}
                                type="image"
                                legacyMode={legacyMode}
                                onUpload={createUploadHandler("image")}
                            />
                        </li>
                    </Permission>
                )}
                <li className={classesRichEditor.menuItem} role="menuitem">
                    <EmbedFlyout
                        createEmbed={(url) => {
                            embedModule.scrapeMedia(url);
                        }}
                        createIframe={(url, frameHeight, frameWidth) => {
                            embedModule.createEmbed({
                                loaderData: {
                                    type: "link",
                                },
                                data: {
                                    url,
                                    embedType: "iframe",
                                    height: frameHeight,
                                    width: frameWidth,
                                },
                            });
                        }}
                        disabled={isLoading}
                        renderAbove={legacyMode}
                    />
                </li>
                {uploadEnabled && (
                    <Permission permission="uploads.add">
                        <li className={classesRichEditor.menuItem} role="menuitem">
                            <EditorUploadButton
                                disabled={isLoading}
                                type="file"
                                legacyMode={legacyMode}
                                onUpload={createUploadHandler("file")}
                            />
                        </li>
                    </Permission>
                )}
                {getExtraComponents().length > 0 && (
                    <li className={classesRichEditor.menuItem} role="separator">
                        <hr className={classesRichEditor.embedBarSeparator} />
                    </li>
                )}
                {getExtraComponents().map((item, i) => (
                    <React.Fragment key={i}>{item}</React.Fragment>
                ))}
            </ul>
        </div>
    );
}

EditorEmbedBar.Item = function EditorEmbedBarItem(props: { children: React.ReactNode }) {
    const { legacyMode } = useEditor();
    const classesRichEditor = richEditorClasses(legacyMode);

    return (
        <li className={classesRichEditor.menuItem} role="menuitem">
            {props.children}
        </li>
    );
};

const extraComponents: React.ReactNode[] = [];
function getExtraComponents() {
    return extraComponents;
}

EditorEmbedBar.addExtraButton = (node: React.ReactNode) => {
    extraComponents.push(node);
};
