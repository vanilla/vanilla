/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import classNames from "classnames";
import { LocationDescriptor } from "history";
import DropDownItem from "./DropDownItem";
import { ModalLink } from "@library/components/modal";
import SmartLink from "@library/components/navigation/SmartLink";

export interface IDropDownItemLink {
    to: LocationDescriptor;
    name: string;
    isModalLink?: boolean;
    children?: React.ReactNode;
    className?: string;
    lang?: string;
}

/**
 * Implements link type of item for DropDownMenu
 */
export default class DropDownItemLink extends React.Component<IDropDownItemLink> {
    public static readonly CSS_CLASS = "dropDownItem-link";

    public render() {
        const { children, name, isModalLink, className, to } = this.props;
        const linkContents = children ? children : name;
        const LinkComponent = isModalLink ? ModalLink : SmartLink;
        return (
            <DropDownItem className={classNames("dropDown-linkItem", className)}>
                <LinkComponent to={to} title={name} lang={this.props.lang} className={DropDownItemLink.CSS_CLASS}>
                    {linkContents}
                </LinkComponent>
            </DropDownItem>
        );
    }
}
