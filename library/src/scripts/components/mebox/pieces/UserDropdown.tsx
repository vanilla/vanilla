/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface IUserDropDownProps {
    className?: string;
}
interface IState {}

/**
 * Implements user dropdown component
 */
export default class UserDropdown extends React.Component<IUserDropDownProps> {
    public render() {
        return <div className={classNames(this.props.className)} />;
    }
}
