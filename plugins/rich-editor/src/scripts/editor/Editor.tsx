/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useMemo, PropsWithChildren } from "react";
import { IEditorProps, EditorContext } from "@rich-editor/editor/context";
import Quill from "quill/core";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { EditorContentContextProvider } from "@rich-editor/editor/contentContext";

/**
 * The editor root.
 *
 * This doesn't actually render any HTML instead.
 * It maintains the context for the rest of the editor pieces.
 * @see EditorContent, EditorInlineMenus, EditorParagraphMenu, etc.
 */
export const Editor = (props: PropsWithChildren<IEditorProps>) => {
    const [editor, setEditorInstance] = useState<Quill | null>(null);
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;

    return (
        <EditorContext.Provider
            value={{
                ...props,
                onFocus: props.onFocus,
                editor,
                setEditorInstance,
                isMobile,
            }}
        >
            <EditorContentContextProvider editor={editor}>{props.children}</EditorContentContextProvider>
        </EditorContext.Provider>
    );
};
