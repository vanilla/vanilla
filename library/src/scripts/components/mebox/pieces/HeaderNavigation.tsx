/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import HeaderNavigationItem, { IHeaderNavigationItemProps } from "./HeaderNavigationItem";

interface IHeaderNavigationProps {
    className?: string;
    data: IHeaderNavigationItemProps[];
}

/**
 * Implements Navigation component for header
 */
export default class HeaderNavigation extends React.Component<IHeaderNavigationProps> {
    public render() {
        const content = this.props.data.map((item, key) => {
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
