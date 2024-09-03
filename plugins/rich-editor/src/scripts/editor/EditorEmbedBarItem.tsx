/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { richEditorClasses } from "@library/editor/richEditorStyles";
import React from "react";

export default function EditorEmbedBarItem(props: { children: React.ReactNode }) {
    const classesRichEditor = richEditorClasses();

    return (
        <li className={classesRichEditor.menuItem} role="menuitem">
            {props.children}
        </li>
    );
}
