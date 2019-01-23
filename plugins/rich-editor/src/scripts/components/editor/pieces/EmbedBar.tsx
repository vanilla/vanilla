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

interface IProps {
    isMobile: boolean;
    isLoading: boolean;
    legacyMode: boolean;
    barRef?: React.RefObject<HTMLDivElement>;
}

export default function EmbedBar(props: IProps) {
    const { isMobile, isLoading, legacyMode } = props;
    const mimeTypes = getMeta("upload.allowedExtensions");

    return (
        <div className="richEditor-embedBar" ref={props.barRef}>
            <ul
                className="richEditor-menuItems richEditor-inlineMenuItems"
                role="menubar"
                aria-label={t("Inline Level Formatting Menu")}
            >
                {isMobile && (
                    <li className="richEditor-menuItem" role="menuitem">
                        <ParagraphDropDown disabled={isLoading} />
                    </li>
                )}
                {!isMobile && (
                    <li className="richEditor-menuItem u-richEditorHiddenOnMobile" role="menuitem">
                        <EmojiPopover disabled={isLoading} renderAbove={legacyMode} />
                    </li>
                )}
                <Permission permission="uploads.add">
                    <li className="richEditor-menuItem" role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="image" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>

                <li className="richEditor-menuItem" role="menuitem">
                    <EmbedPopover disabled={isLoading} />
                </li>

                <Permission permission="uploads.add">
                    <li className="richEditor-menuItem" role="menuitem">
                        <EditorUploadButton disabled={isLoading} type="file" allowedMimeTypes={mimeTypes} />
                    </li>
                </Permission>
            </ul>
        </div>
    );
}
