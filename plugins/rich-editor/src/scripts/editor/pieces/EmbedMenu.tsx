/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createContext, PropsWithChildren, ReactNode, useRef, useState } from "react";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import { EditorEventWall } from "@rich-editor/editor/pieces/EditorEventWall";
import classNames from "classnames";
import { cx } from "@emotion/css";
import { useFocusWatcher } from "@vanilla/react-utils";

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
        <div {...restProps} role="toolbar" className={cx({ [classes.root]: true, isOpened }, props.className)}>
            {children}
        </div>
    );
}
