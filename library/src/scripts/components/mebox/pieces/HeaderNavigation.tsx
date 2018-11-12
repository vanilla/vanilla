/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import HeaderNavigationItem, { IHeaderNavigationItemProps } from "./HeaderNavigationItem";

export interface IHeaderNavigationProps {
    className?: string;
    children: IHeaderNavigationItemProps[];
}

/**
 * Implements Navigation component for header
 */
export default class HeaderNavigation extends React.Component<IHeaderNavigationProps> {
    public render() {
        console.log("props: ", this.props);
        const content = this.props.children.map((item, key) => {
            return (
                <React.Fragment key={`headerNavItem-${key}`}>
                    <HeaderNavigationItem {...item} />
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
