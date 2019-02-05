/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IMe } from "@library/@types/api/users";
import { IMessagesContentsProps } from "@library/components/mebox/pieces/MessagesContents";
import classNames from "classnames";
import * as React from "react";
import MessagesDropDown from "./pieces/MessagesDropDown";
import NotificationsDropdown from "./pieces/NotificationsDropDown";
import UserDropdown from "./pieces/UserDropdown";

export interface IMeBoxProps {
    className?: string;
    messagesProps: IMessagesContentsProps;
    counts: any;
    countsClass?: string;
    buttonClassName?: string;
    contentClassName?: string;
    currentUser: IMe;
}

/**
 * Implements MeBox component. Note that on mobile we use the CompactMeBox component but they share children components
 */
export default class MeBox extends React.Component<IMeBoxProps> {
    public render() {
        const { buttonClassName, contentClassName, countsClass, currentUser } = this.props;
        return (
            <div className={classNames("meBox", this.props.className)}>
                <NotificationsDropdown
                    userSlug={currentUser.name}
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                />
                <MessagesDropDown
                    {...this.props.messagesProps}
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    toggleContentsClassName="meBox-buttonContent"
                />
                <UserDropdown
                    counts={this.props.counts}
                    className="meBox-userDropdown"
                    countsClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    toggleContentClassName="meBox-buttonContent"
                />
            </div>
        );
    }
}
