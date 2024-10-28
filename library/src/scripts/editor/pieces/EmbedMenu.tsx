/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { embedMenuClasses } from "@library/editor/pieces/embedMenuStyles";
import React, { PropsWithChildren } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {}

export function EmbedMenu(props: PropsWithChildren<IProps> & { isOpened?: boolean }) {
    const { children, isOpened, ...restProps } = props;
    const classes = embedMenuClasses();
    return (
        <div {...restProps} role="toolbar" className={cx(classes.root, { isOpened }, props.className)}>
            {children}
        </div>
    );
}
