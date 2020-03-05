/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { EditorEventWall } from "@rich-editor/editor/pieces/EditorEventWall";

interface IProps {
    children: React.ReactNode;
}

/**
 * A class for rendering Giphy embeds.
 */
export function EmbedMenu(props: IProps) {
    const classes = embedMenuClasses();
    return (
        <EditorEventWall>
            <div className={classes.root}>{props.children}</div>
        </EditorEventWall>
    );
}
