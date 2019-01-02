/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import NotificationsDropdown from "./pieces/NotificationsDropDown";
import MessagesDropDown from "./pieces/MessagesDropDown";
import UserDropdown from "./pieces/UserDropdown";
import { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";

export interface IMeBoxProps {
    className?: string;
    notificationsProps: INotificationsProps;
    messagesProps: IMessagesContentsProps;
    counts: any;
    countsClass?: string;
    buttonClassName?: string;
    contentClassName?: string;
}

/**
 * Implements MeBox component. Note that on mobile we use the CompactMeBox component but they share children components
 */
export default class MeBox extends React.Component<IMeBoxProps> {
    public render() {
        const countClass = this.props.countsClass;
        const buttonClassName = this.props.buttonClassName;
        const contentClassName = this.props.contentClassName;
        return (
            <div className={classNames("meBox", this.props.className)}>
                <NotificationsDropdown
                    {...this.props.notificationsProps}
                    countClass={countClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                />
                <MessagesDropDown
                    {...this.props.messagesProps}
                    countClass={countClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                />
                <UserDropdown
                    counts={this.props.counts}
                    className="meBox-userDropdown"
                    countsClass={countClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                />
            </div>
        );
    }
}
