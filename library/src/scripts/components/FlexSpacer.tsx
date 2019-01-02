/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

interface IProps {
    className: string;
    children?: React.ReactNode;
    tag?: string;
}

/**
 * Implements Flex Spacer component - to keep flexed iteams centered, when the components in the flex box are not symmetric
 */
export default class FlexSpacer extends React.Component<IProps> {
    public render() {
        const content = ` `;
        const Tag = `${this.props.tag ? this.props.tag : "div"}`;
        return (
            <Tag className={classNames("u-flexSpacer", this.props.className)} aria-hidden={true} tabIndex={-1}>
                {content}
                {this.props.children && <span className="sr-only">{this.props.children}</span>}
            </Tag>
        );
    }
}
