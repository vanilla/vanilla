/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface INotificationsDropDownProps {
    className?: string;
    count?: number;
}

/**
 * Implements Notifications Dropdown for header
 */
export default class NotificationsDropDown extends React.Component<INotificationsDropDownProps> {
    public render() {
        return <div className={classNames(this.props.className)} />;
    }
}
