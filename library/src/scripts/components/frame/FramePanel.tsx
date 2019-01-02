/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements stackable "panel" inside the FrameBody. An example would be navigation, you
 * might go up and down the tree, and stack each level on top of each other.
 */
export default class FramePanel extends React.PureComponent<IProps> {
    public render() {
        return <div className={classNames("framePanel", this.props.className)}>{this.props.children}</div>;
    }
}
