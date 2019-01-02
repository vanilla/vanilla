/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

export interface IFrameFooterProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Generic footer for frame component
 */
export default class FrameFooter extends React.PureComponent<IFrameFooterProps> {
    public static defaultProps = {
        validSelection: false,
    };

    public render() {
        return <footer className={classNames("frameFooter", this.props.className)}>{this.props.children}</footer>;
    }
}
