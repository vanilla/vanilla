/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useEditor } from "@rich-editor/editor/context";
import ParagraphMenusBarToggle from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { useLayout } from "@library/layout/LayoutContext";

export function EditorParagraphMenu() {
    const { isLoading, quill } = useEditor();
    const { isCompact } = useLayout();
    if (!quill || isLoading || isCompact) {
        return null;
    } else {
        return <ParagraphMenusBarToggle />;
    }
}
