/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { History } from "history";
import LocationDescriptor = History.LocationDescriptor;
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import SmartLink from "@library/routing/links/SmartLink";

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
    public render() {
        const { children, name, isModalLink, className, to } = this.props;
        const linkContents = children ? children : name;
        const LinkComponent = isModalLink ? ModalLink : SmartLink;
        const classesDropDown = dropDownClasses();
        return (
            <DropDownItem className={classNames(className, classesDropDown.item)}>
                <LinkComponent to={to} title={name} lang={this.props.lang} className={classesDropDown.action}>
                    {linkContents}
                </LinkComponent>
            </DropDownItem>
        );
    }
}
