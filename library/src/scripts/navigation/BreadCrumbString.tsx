/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";

export interface ICrumbString {
    name: string;
}

export interface IProps {
    crumbs: ICrumbString[];
    className?: string;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class BreadCrumbString extends React.Component<IProps> {
    public render() {
        const crumbSeparator = ` › `;
        const crumbCount = this.props.crumbs.length - 1;

        const contents = this.props.crumbs.map((crumb, index) => {
            const lastElement = index === crumbCount;
            return crumb.name + (lastElement ? "" : crumbSeparator);
        });

        return <span className={classNames("breadCrumbAsString", this.props.className)}>{contents}</span>;
    }
}
