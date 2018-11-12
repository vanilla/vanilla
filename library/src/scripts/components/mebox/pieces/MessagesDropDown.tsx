/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface IMessagesDropDownProps {
    className?: string;
}
interface IState {}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesDropdown extends React.Component<IMessagesDropDownProps> {
    public render() {
        return <div className={classNames(this.props.className)} />;
    }
}
