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
}

/**
 * Generic footer for frame component
 */
export default class FrameFooter extends React.PureComponent<IFrameFooterProps> {
    public static defaultProps = {
        validSelection: false,
    };

    public render() {
        const classes = frameFooterClasses();
        return (
            <footer
                className={classNames(
                    "frameFooter",
                    this.props.className,
                    classes.root,
                    this.props.justifyRight && classes.justifiedRight,
                    this.props.selfPadded ? classes.selfPadded : "",
                )}
            >
                {this.props.children}
            </footer>
        );
    }
}
