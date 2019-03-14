/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDownItemLink, { IDropDownItemLink } from "DropDownItemLink";
import NumberFormatted from "../../content/NumberFormatted";
import classNames from "classnames";
import { dropDownClasses } from "../dropDownStyles";

interface IProps extends IDropDownItemLink {
    count?: number;
    hideCountWhenZero?: boolean;
    className?: string;
    countsClass?: string;
}

/**
 * Implements link type of item with count for DropDown menu
 */
export default class DropDownItemLinkWithCount extends React.Component<IProps> {
    public static defaultProps = {
        hideCountWhenZero: true,
    };
    public render() {
        const { name, children, count } = this.props;
        const linkContents = children ? children : name;
        const showCount = !!count && !(this.props.hideCountWhenZero && this.props.count === 0);
        const classesDropDown = dropDownClasses();
        return (
            <DropDownItemLink {...this.props}>
                <span className={classNames("dropDownItem-text", classesDropDown.text)}>{linkContents}</span>
                {showCount && (
                    <NumberFormatted
                        className={classNames("dropDownItem-count", classesDropDown.count, this.props.countsClass)}
                        value={count!}
                    />
                )}
            </DropDownItemLink>
        );
    }
}
