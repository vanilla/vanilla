/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import { useLayout } from "@library/layout/LayoutContext";
import { ILayoutMediaQueryFunction } from "@library/layout/types/LayoutUtils";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default class DropDownItemMeta extends React.Component<IProps> {
    public render() {
        const { mediaQueries } = useLayout();
        const classes = dropDownClasses({ mediaQueries });
        return <div className={classNames(this.props.className, classes.metaItem)}>{this.props.children}</div>;
    }
}
