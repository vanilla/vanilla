/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useEditor } from "@rich-editor/editor/context";
import InlineToolbar from "@rich-editor/toolbars/InlineToolbar";
import MentionToolbar from "@rich-editor/toolbars/MentionToolbar";

export function EditorInlineMenus() {
    const { quill, isLoading } = useEditor();

    if (!quill || isLoading) {
        return null;
    } else {
        return (
            <>
                <InlineToolbar />
                <MentionToolbar />
            </>
        );
    }
}
