/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { visibility } from "@library/styles/styleHelpers";
import classNames from "classnames";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    tag?: ElementType;
}

/**
 * Children of component only visible to screen readers
 */
export default class ScreenReaderContent extends React.PureComponent<IProps> {
    public render() {
        const Tag = (this.props.tag || "div") as "div";
        return (
            <Tag {...this.props} className={classNames(visibility().visuallyHidden, "sr-only")}>
                {this.props.children}
            </Tag>
        );
    }
}
