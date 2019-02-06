/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IMe, IUserFragment } from "@library/@types/api/users";
import classNames from "classnames";
import * as React from "react";
import MessagesDropDown from "./pieces/MessagesDropDown";
import NotificationsDropdown from "./pieces/NotificationsDropDown";
import UserDropdown from "./pieces/UserDropdown";
import { IInjectableUserState } from "@library/users/UsersModel";
import get from "lodash/get";

export interface IMeBoxProps extends IInjectableUserState {
    countClass?: string;
    className?: string;
    countsClass?: string;
    buttonClassName?: string;
    contentClassName?: string;
}

/**
 * Implements MeBox component. Note that on mobile we use the CompactMeBox component but they share children components
 */
export default class MeBox extends React.Component<IMeBoxProps> {
    public render() {
        const { buttonClassName, contentClassName, countsClass } = this.props;
        const userInfo: IMe = get(this.props, "currentUser.data", {
            countUnreadNotifications: 0,
            name: null,
            userID: null,
            photoUrl: null,
        });

        return (
            <div className={classNames("meBox", this.props.className)}>
                <NotificationsDropdown
                    userSlug={userInfo.name}
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    countUnread={userInfo.countUnreadNotifications}
                />
                <MessagesDropDown
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    toggleContentsClassName="meBox-buttonContent"
                />
                <UserDropdown
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
