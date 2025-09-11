/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import classNames from "classnames";

export interface IFrameFooterProps {
    className?: string;
    children: React.ReactNode;
    selfPadded?: boolean;
    justifyRight?: boolean;
    forDashboard?: boolean;
}

/**
 * Generic footer for frame component
 */
export default function FrameFooter(props: IFrameFooterProps) {
    const classes = frameFooterClasses.useAsHook();
    return (
        <footer
            className={classNames(
                "frameFooter",
                classes.root,
                props.className,
                props.justifyRight && classes.justifiedRight,
                props.selfPadded ? classes.selfPadded : "",
                props.forDashboard ? classes.forDashboard : "",
            )}
        >
            {props.children}
        </footer>
    );
}
