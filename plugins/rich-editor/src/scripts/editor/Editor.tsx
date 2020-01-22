/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useMemo } from "react";
import { IEditorProps, EditorContext } from "@rich-editor/editor/context";
import Quill from "quill/core";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import { EditorContentContextProvider } from "@rich-editor/editor/contentContext";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import { visibility } from "@vanilla/library/src/scripts/styles/styleHelpers";

/**
 * The editor root.
 *
 * This doesn't actually render any HTML instead.
 * It maintains the context for the rest of the editor pieces.
 * @see EditorContent, EditorInlineMenus, EditorParagraphMenu, etc.
 */
export const Editor = (props: IEditorProps) => {
    const [quill, setQuillInstance] = useState<Quill | null>(null);
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;

    return (
        <EditorContext.Provider
            value={{
                ...props,
                quill,
                setQuillInstance,
                isMobile,
            }}
        >
            <EditorContentContextProvider quill={quill}>{props.children}</EditorContentContextProvider>
        </EditorContext.Provider>
    );
};
