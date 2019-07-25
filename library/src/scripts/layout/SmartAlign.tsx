/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { smartAlignClasses } from "@library/layout/smartAlignStyles";

interface IProps {
    className?: string;
    children: React.ReactNode;
    outerTag?: keyof JSX.IntrinsicElements;
    innerTag?: keyof JSX.IntrinsicElements;
}

export default class SmartAlign extends React.Component<IProps> {
    public render() {
        const Outer = this.props.outerTag || "div";
        const Inner = this.props.outerTag || "div";
        const classes = smartAlignClasses();
        return (
            <Outer className={classNames("smartAlign-outer", this.props.className, classes.root)}>
                <Inner className={classNames("smartAlign-inner", classes.inner)}>{this.props.children}</Inner>
            </Outer>
        );
    }
}
