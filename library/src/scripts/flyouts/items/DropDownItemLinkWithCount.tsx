/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemLink, { IDropDownItemLink } from "@library/flyouts/items/DropDownItemLink";
import NumberFormatted from "@library/content/NumberFormatted";
import classNames from "classnames";

interface IProps extends IDropDownItemLink {
    count?: number;
    hideCountWhenZero?: boolean;
    className?: string;
    countsClass?: string;
}

/**
 * Implements link type of item with count for DropDown menu
 */
export default function DropDownItemLinkWithCount(props: IProps) {
    const { name, hideCountWhenZero = true, children, count } = props;
    const linkContents = children ? children : name;
    const showCount = !!count && !(hideCountWhenZero && props.count === 0);
    const classesDropDown = dropDownClasses.useAsHook();
    return (
        <DropDownItemLink {...props}>
            <span className={classNames("dropDownItem-text", classesDropDown.text)}>{linkContents}</span>
            {showCount && (
                <NumberFormatted
                    className={classNames("dropDownItem-count", classesDropDown.count, props.countsClass)}
                    value={count!}
                />
            )}
        </DropDownItemLink>
    );
}
