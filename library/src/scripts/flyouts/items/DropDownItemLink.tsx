/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import { LocationDescriptor } from "history";
import React from "react";
import { CheckCompactIcon } from "@library/icons/common";
import { cx } from "@emotion/css";

export interface IDropDownItemLink {
    to: LocationDescriptor;
    name?: string;
    children?: React.ReactNode;
    className?: string;
    lang?: string;
    isChecked?: boolean;
    isActive?: boolean;
    isBasicLink?: boolean;
}

/**
 * Implements link type of item for DropDownMenu
 */
export default function DropDownItemLink(props: IDropDownItemLink) {
    const { children, name, className, to, isBasicLink } = props;
    const linkContents = children ? children : name;
    const classes = dropDownClasses.useAsHook();

    return (
        <DropDownItem className={cx(className, classes.item)}>
            {isBasicLink ? (
                <a href={to as string} className={cx(classes.action, props.isActive && classes.actionActive)}>
                    {linkContents}
                    {props.isChecked && <CheckCompactIcon className={classes.check} aria-hidden={true} />}
                </a>
            ) : (
                <SmartLink
                    to={to}
                    title={name}
                    lang={props.lang}
                    className={cx(classes.action, props.isActive && classes.actionActive)}
                >
                    {linkContents}
                    {props.isChecked && <CheckCompactIcon className={classes.check} aria-hidden={true} />}
                </SmartLink>
            )}
        </DropDownItem>
    );
}
