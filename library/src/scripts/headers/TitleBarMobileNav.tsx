/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import classNames from "classnames";
import { getCurrentLocale } from "@vanilla/i18n";
import { navigationVariables } from "./navigationVariables";
import { defaultNavigationData } from "./mebox/state/defaultNavigationData";
//import TitleBarNavItem from "./mebox/pieces/TitleBarNavItem";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";

export interface ITitleBarMobileNavProps {
    className?: string;
    linkClassName?: string;
    linkContentClassName?: string;
    listClassName?: string;
    data?: [];
}

/**
 * Implements MobileNavigation component for header
 */
export default class TitleBarMobileNav extends React.Component<ITitleBarMobileNavProps> {
    public render() {
        const classes = titleBarNavClasses();
        const dataLength = this.props.data ? Object.keys(this.props.data!).length - 1 : 0;
        const navigationData = Object.keys(navigationVariables()[`${getCurrentLocale()}`]).includes("data")
            ? navigationVariables()[`${getCurrentLocale()}`].data
            : defaultNavigationData().data;

        const content = navigationData.map((item, key) => {
            return (
                <DropDownItemLink key={key} to={item.to}>
                    {item.children}
                </DropDownItemLink>
            );
        });

        return (
            <nav>
                <ul>{content}</ul>
            </nav>
        );
    }
}
