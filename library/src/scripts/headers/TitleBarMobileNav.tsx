/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import Permission from "@library/features/users/Permission";

export interface ITitleBarMobileNavProps {
    data: any[];
}

/**
 * Implements MobileNavigation component for header
 */
export default class TitleBarMobileNav extends React.Component<ITitleBarMobileNavProps> {
    public render() {
        const classes = titleBarNavClasses();

        const { data } = this.props;

        const dataLength = data.length - 1;
        const content = data.map((item, key) => {
            const component = (
                <div key={key}>
                    <DropDownItemLink className={classes.navLinks} key={key} to={item.to}>
                        {item.children}
                    </DropDownItemLink>
                    {dataLength > key && <DropDownItemSeparator />}
                </div>
            );
            if (item.permission) {
                return (
                    <Permission key={key} permission={item.permission}>
                        {component}
                    </Permission>
                );
            } else {
                return component;
            }
        });

        return (
            <nav>
                <ul>{content}</ul>
            </nav>
        );
    }
}
