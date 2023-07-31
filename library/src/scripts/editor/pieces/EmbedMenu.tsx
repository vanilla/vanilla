/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createContext, PropsWithChildren, useState } from "react";
import { embedMenuClasses } from "@library/editor/pieces/embedMenuStyles";
import { EditorEventWall } from "@library/editor/pieces/EditorEventWall";
import { cx } from "@emotion/css";

interface IEmbedMenuContext {
    selected: string | undefined;
    setSelected(name: string | undefined);
}

export const EmbedMenuContext = createContext<IEmbedMenuContext>({
    selected: undefined,
    setSelected: () => {},
});

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

/**
 * Renders an embed menu and manages its context.
 */
export function EditorEmbedMenu(props: PropsWithChildren<IProps>) {
    const [selected, setSelected] = useState<string | undefined>();
    return (
        <EmbedMenuContext.Provider value={{ selected, setSelected }}>
            <EditorEventWall>
                <EmbedMenu {...props} isOpened={selected != null} />
            </EditorEventWall>
        </EmbedMenuContext.Provider>
    );
}

/**
 * Renders an embed menu and manages its context.
 */
export function EmbedMenu(props: PropsWithChildren<IProps> & { isOpened?: boolean }) {
    const { children, isOpened, ...restProps } = props;
    const classes = embedMenuClasses();
    return (
        <div {...restProps} role="toolbar" className={cx(classes.root, { isOpened }, props.className)}>
            {children}
        </div>
    );
}
