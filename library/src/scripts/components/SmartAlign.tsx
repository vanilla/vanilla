/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { smartAlignClasses } from "@library/styles/smartAlignStyles";

interface IProps {
    className?: string;
    children: React.ReactNode;
    outerTag?: string;
    innerTag?: string;
}

export default class SmartAlign extends React.Component<IProps> {
    public render() {
        const Outer = this.props.outerTag ? `${this.props.outerTag}` : "div";
        const Inner = this.props.outerTag ? `${this.props.innerTag}` : "div";
        const classes = smartAlignClasses();
        return (
            <Outer className={classNames("smartAlign-outer", this.props.className, classes.root)}>
                <Inner className={classNames("smartAlign-inner", classes.inner)}>{this.props.children}</Inner>
            </Outer>
        );
    }
}
