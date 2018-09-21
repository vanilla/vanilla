/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";

export interface IDropDownItemLink {
    url: string;
    name: string;
}

export default class DropDownItemLink extends React.Component<IDropDownItemLink> {
    public render() {
        return (
            <DropDownItem>
                <NavLink
                    to={this.props.url}
                    title={this.props.name}
                    activeClassName="isCurrent"
                >
                    {this.props.name}
                </NavLink>
            </DropDownItem>
        );
    }
}
