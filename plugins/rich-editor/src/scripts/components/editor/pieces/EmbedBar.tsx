/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t, getMeta } from "@library/application";
import ParagraphDropDown from "@rich-editor/components/toolbars/ParagraphDropDown";
import EmojiPopover from "@rich-editor/components/popovers/EmojiPopover";
import Permission from "@library/users/Permission";
import EmbedPopover from "@rich-editor/components/popovers/EmbedPopover";
import EditorUploadButton from "@rich-editor/components/editor/pieces/EditorUploadButton";
import { richEditorClasses } from "@rich-editor/styles/richEditorStyles/richEditorClasses";
import classNames from "classnames";

interface IProps {
    isMobile: boolean;
    isLoading: boolean;
    legacyMode: boolean;
    barRef?: React.RefObject<HTMLDivElement>;
}

export default function EmbedBar(props: IProps) {
    const { isMobile, isLoading, legacyMode } = props;
    const mimeTypes = getMeta("upload.allowedExtensions");
    const classesRichEditor = richEditorClasses();

    return (
        <div className={classNames("richEditor-embedBar", classesRichEditor.embedBar)} ref={props.barRef}>
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
                        <ParagraphDropDown disabled={isLoading} />
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
                        <EmojiPopover disabled={isLoading} renderAbove={legacyMode} />
                    </li>
                )}
                <Permission permission="uploads.add">
                    <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="image" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>

                <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                    <EmbedPopover disabled={isLoading} />
                </li>

                <Permission permission="uploads.add">
                    <li className={classNames("richEditor-menuItem", classesRichEditor.menuItem)} role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="file" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>
            </ul>
        </div>
    );
}
