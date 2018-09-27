/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";
import classNames from "classnames";

export interface IDropDownItemLink {
    url: string;
    name: string;
    children?: React.ReactNode;
    className?: string;
}

/**
 * Implements link type of item for DropDownMenu
 */
export default class DropDownItemLink extends React.Component<IDropDownItemLink> {
    public render() {
        const linkContents = this.props.children ? this.props.children : this.props.name;
        return (
            <DropDownItem className={classNames("dropDown-linkItem", this.props.className)}>
                <NavLink to={this.props.url} title={this.props.name} activeClassName="isCurrent">
                    {linkContents}
                </NavLink>
            </DropDownItem>
        );
    }
}
