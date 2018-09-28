/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import classNames from "classnames";
import { LocationDescriptor } from "history";
import DropDownItem from "./DropDownItem";

export interface IDropDownItemLink {
    to: LocationDescriptor;
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
                <NavLink
                    to={this.props.to}
                    title={this.props.name}
                    className="dropDownItem-link"
                    activeClassName="isCurrent"
                >
                    {linkContents}
                </NavLink>
            </DropDownItem>
        );
    }
}
