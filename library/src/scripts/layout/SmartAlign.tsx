/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { smartAlignClasses } from "@library/layout/smartAlignStyles";
import { cx } from "@emotion/css";

interface IProps {
    className?: string;
    children: React.ReactNode;
    outerTag?: ElementType;
    innerTag?: ElementType;
}

export default class SmartAlign extends React.Component<IProps> {
    public render() {
        const Outer = this.props.outerTag || "div";
        const Inner = this.props.outerTag || "div";
        const classes = smartAlignClasses();
        return (
            <Outer className={cx(classes.root, this.props.className)}>
                <Inner className={cx(classes.inner)}>{this.props.children}</Inner>
            </Outer>
        );
    }
}
