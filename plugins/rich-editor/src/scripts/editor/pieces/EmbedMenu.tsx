/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createContext, PropsWithChildren, ReactNode, useRef, useState } from "react";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { EditorEventWall } from "@rich-editor/editor/pieces/EditorEventWall";
import classNames from "classnames";

interface IEmbedMenuContext {
    selected: string | undefined;
    setSelected(name: string | undefined);
}

export const EmbedMenuContext = createContext<IEmbedMenuContext>({
    selected: undefined,
    setSelected: () => {},
});

interface IProps {}

/**
 * Renders an embed menu and manages it's context.
 */
export function EmbedMenu(props: PropsWithChildren<IProps>) {
    const { children } = props;
    const classes = embedMenuClasses();
    const [selected, setSelected] = useState<string | undefined>();
    const menuRef = useRef<HTMLDivElement | null>(null);
    return (
        <EmbedMenuContext.Provider value={{ selected, setSelected }}>
            <EditorEventWall>
                <div
                    role="toolbar"
                    ref={menuRef}
                    className={classNames({ [classes.root]: true, isOpened: selected != null })}
                >
                    {children}
                </div>
            </EditorEventWall>
        </EmbedMenuContext.Provider>
    );
}
