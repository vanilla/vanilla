/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import VanillaHeaderNavItem, { IHeaderNav } from "@library/headers/mebox/pieces/VanillaHeaderNavItem";
import vanillaHeaderNavClasses from "@library/headers/vanillaHeaderNavStyles";
import classNames from "classnames";

export interface IVanillaHeaderNavProps {
    className?: string;
    linkClassName?: string;
    linkContentClassName?: string;
    listClassName?: string;
    data?: IHeaderNav[];
    children?: React.ReactNode;
    wrapper?: JSX.Element;
}

/**
 * Implements Navigation component for header
 */
export default class VanillaHeaderNav extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const classes = vanillaHeaderNavClasses();
        const dataLength = Object.keys(this.props.data!).length - 1;
        const content = !!this.props.data
            ? this.props.data.map((item, key) => {
                  return (
                      <VanillaHeaderNavItem
                          {...item}
                          linkContentClassName={classNames(
                              this.props.linkContentClassName,
                              key === dataLength ? classes.lastItem : false,
                          )}
                          linkClassName={this.props.linkClassName}
                          key={`headerNavItem-${key}`}
                      />
                  );
              })
            : null;

        return (
            <nav className={classNames("headerNavigation", this.props.className, classes.navigation)}>
                <ul className={classNames("headerNavigation-items", this.props.listClassName, classes.items)}>
                    {!!this.props.children ? this.props.children : content}
                </ul>
            </nav>
        );
    }
}
