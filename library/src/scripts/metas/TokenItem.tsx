/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { TokenItemClasses } from "@library/metas/TokenItem.styles";
import React, { ReactNode } from "react";

interface IProps extends React.HTMLAttributes<HTMLSpanElement> {
    classNames?: string;
    children: ReactNode;
}

export const TokenItem = React.forwardRef(function TokenItemImpl(props: IProps, ref: React.RefObject<HTMLDivElement>) {
    return (
        <span {...props} ref={ref} className={cx(TokenItemClasses().root, props.className)}>
            {props.children}
        </span>
    );
});
