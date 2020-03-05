/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { flexHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";

interface IProps {
    className?: string;
    children?: React.ReactNode;
    tag?: keyof JSX.IntrinsicElements;
    actualSpacer?: boolean;
}

const flexSpacer = style({
    $debugName: "flexSpacer",
    flex: 1,
});

/**
 * Implements Flex Spacer component - to keep flexed iteams centered, when the components in the flex box are not symmetric
 */
export default class FlexSpacer extends React.Component<IProps> {
    public render() {
        const content = ` `;
        const Tag = this.props.tag || "div";
        return (
            <Tag
                className={classNames(this.props.className, this.props.actualSpacer && flexSpacer)}
                aria-hidden={true}
                tabIndex={-1}
            >
                {content}
                {this.props.children && <span className="sr-only">{this.props.children}</span>}
            </Tag>
        );
    }
}
