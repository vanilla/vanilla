/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import VanillaHeaderNavItem, { IVanillaHeaderNavItemProps } from "./VanillaHeaderNavItem";

export interface IVanillaHeaderNavProps {
    className?: string;
    children: IVanillaHeaderNavItemProps[];
    linkClassName?: string;
    linkContentClassName?: string;
}

/**
 * Implements Navigation component for header
 */
export default class VanillaHeaderNav extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const content = this.props.children.map((item, key) => {
            return (
                <React.Fragment key={`headerNavItem-${key}`}>
                    <VanillaHeaderNavItem
                        {...item}
                        linkContentClassName={this.props.linkContentClassName}
                        linkClassName={this.props.linkClassName}
                    />
                </React.Fragment>
            );
        });
        return (
            <nav className={classNames("headerNavigation", this.props.className)}>
                <ul className="headerNavigation-items">{content}</ul>
            </nav>
        );
    }
}
