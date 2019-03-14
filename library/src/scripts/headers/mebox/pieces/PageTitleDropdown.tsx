/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface IPageTitleDropDownProps {
    className?: string;
}
interface IState {}

/**
 * Implements PageTitleDropDown, used for Mobile
 */
export default class PageTitleDropdown extends React.Component<IPageTitleDropDownProps> {
    public render() {
        return <div className={classNames(this.props.className)} />;
    }
}
