/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t, getMeta } from "../../../../../../library/src/scripts/dom/appUtils";
import ParagraphDropDown from "../../toolbars/ParagraphDropDown";
import EmojiFlyout from "../../flyouts/EmojiFlyout";
import Permission from "../../../../../../library/src/scripts/features/users/Permission";
import EmbedPopover from "../../flyouts/EmbedFlyout";
import EditorUploadButton from "EditorUploadButton";
import { richEditorClasses } from "../richEditorClasses";
import classNames from "classnames";
import { richEditorFormClasses } from "../richEditorFormClasses";

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
    const classesRichEditorForm = richEditorFormClasses();

    return (
        <div className={classNames("richEditor-embedBar", classesRichEditor.embedBar)} ref={props.barRef}>
            <ul
                className={classNames(
                    "richEditor-menuItems",
                    "richEditor-inlineMenuItems",
                    classesRichEditor.menuItems,
                    classesRichEditorForm.inlineMenuItems,
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
                        <EmojiFlyout disabled={isLoading} renderAbove={legacyMode} />
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
