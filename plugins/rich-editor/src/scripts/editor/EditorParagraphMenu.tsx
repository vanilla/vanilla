/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useEditor } from "@rich-editor/editor/context";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import ParagraphMenusBarToggle from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";

export function EditorParagraphMenu() {
    const { isLoading, quill } = useEditor();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    if (!quill || isLoading || isMobile) {
        return null;
    } else {
        return <ParagraphMenusBarToggle />;
    }
}
