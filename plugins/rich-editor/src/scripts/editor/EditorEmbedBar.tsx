/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { getMeta, t } from "@library/utility/appUtils";
import Permission from "@library/features/users/Permission";
import EditorUploadButton from "@rich-editor/editor/pieces/EditorUploadButton";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import EmojiFlyout from "@rich-editor/flyouts/EmojiFlyout";
import EmbedFlyout from "@rich-editor/flyouts/EmbedFlyout";
import ParagraphMenusBarToggle from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { useEditor } from "@rich-editor/editor/context";

interface IProps {
    className?: string;
    contentRef?: React.RefObject<HTMLDivElement>;
}

export function EditorEmbedBar(props: IProps) {
    const { isMobile, isLoading, legacyMode, quill } = useEditor();
    if (!quill) {
        return null;
    }
    const mimeTypes = getMeta("upload.allowedExtensions");
    const classesRichEditor = richEditorClasses(legacyMode);

    return (
        <div
            ref={props.contentRef}
            className={classNames("richEditor-embedBar", props.className, classesRichEditor.embedBar)}
        >
            <ul
                className={classNames(
                    "richEditor-menuItems",
                    "richEditor-inlineMenuItems",
                    classesRichEditor.menuItems,
                )}
                role="menubar"
                aria-label={t("Inline Level Formatting Menu")}
            >
                {isMobile && (
                    <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                        <ParagraphMenusBarToggle renderAbove={legacyMode} disabled={isLoading} mobile={true} />
                    </li>
                )}
                {!isMobile && (
                    <li
                        className={classNames(
                            "richEditor-menuItem",
                            "u-richEditorHiddenOnMobile",
                            classesRichEditor.menuItem,
                        )}
                        role="menuitem"
                    >
                        <EmojiFlyout disabled={isLoading} renderAbove={legacyMode} legacyMode={legacyMode} />
                    </li>
                )}
                <Permission permission="uploads.add">
                    <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="image" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>
                <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                    <EmbedFlyout disabled={isLoading} renderAbove={legacyMode} />
                </li>
                <Permission permission="uploads.add">
                    <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="file" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>
                {EditorEmbedBar.extraComponents.map((item, i) => (
                    <React.Fragment key={i}>{item}</React.Fragment>
                ))}
            </ul>
        </div>
    );
}

EditorEmbedBar.Item = function EditorEmbedBarItem(props: { children: React.ReactNode }) {
    const { isMobile, legacyMode } = useEditor();
    const classesRichEditor = richEditorClasses(legacyMode, isMobile);

    return (
        <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
            {props.children}
        </li>
    );
};

EditorEmbedBar.extraComponents = [] as React.ReactNode[];

EditorEmbedBar.addAdditionItem = (node: React.ReactNode) => {
    EditorEmbedBar.extraComponents.push(node);
};
