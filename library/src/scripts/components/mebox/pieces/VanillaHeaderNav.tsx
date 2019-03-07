/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import vanillaHeaderNavClasses from "@library/components/headers/VanillaHeaderNav";
import VanillaHeaderNavItem, { IHeaderNav } from "@library/components/mebox/pieces/VanillaHeaderNavItem";

export interface IVanillaHeaderNavProps {
    className?: string;
    linkClassName?: string;
    linkContentClassName?: string;
    listClassName?: string;
    data?: IHeaderNav[];
    children?: React.ReactNode;
}

/**
 * Implements Navigation component for header
 */
export default class VanillaHeaderNav extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const classes = vanillaHeaderNavClasses();
        const content = !!this.props.data
            ? this.props.data.map((item, key) => {
                  return (
                      <VanillaHeaderNavItem
                          {...item}
                          linkContentClassName={this.props.linkContentClassName}
                          linkClassName={this.props.linkClassName}
                          key={`headerNavItem-${key}`}
                      />
                  );
              })
            : null;

        return (
            <nav className={classNames("headerNavigation", this.props.className, classes.navigation)}>
                <ul className={classNames("headerNavigation-items", this.props.listClassName, classes.items)}>
                    {!!content && content}
                    {!!this.props.children && this.props.children}
                </ul>
            </nav>
        );
    }
}
