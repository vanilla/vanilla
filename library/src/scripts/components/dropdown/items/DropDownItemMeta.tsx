/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default class DropDownItemMeta extends React.Component<IProps> {
    public render() {
        return (
            <div className={classNames("dropDown-meta", "metaStyle", this.props.className)}>{this.props.children}</div>
        );
    }
}
