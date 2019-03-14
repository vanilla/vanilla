/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IMe } from "@library/@types/api";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import UserDropdown from "@library/headers/mebox/pieces/UserDropdown";
import MessagesDropDown from "@library/headers/mebox/pieces/MessagesDropDown";
import { IInjectableUserState } from "@library/features/users/UsersModel";

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
        const classes = meBoxClasses();

        return (
            <div className={classNames("meBox", this.props.className, classes.root)}>
                <NotificationsDropdown
                    userSlug={userInfo.name}
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    countUnread={userInfo.countUnreadNotifications}
                    toggleContentClassName={classNames("meBox-buttonContent", classes.buttonContent)}
                />
                <MessagesDropDown
                    countClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    toggleContentClassName={classNames("meBox-buttonContent", classes.buttonContent)}
                />
                <UserDropdown
                    className="meBox-userDropdown"
                    countsClass={countsClass}
                    buttonClassName={buttonClassName}
                    contentsClassName={contentClassName}
                    toggleContentClassName={classNames("meBox-buttonContent", classes.buttonContent)}
                />
            </div>
        );
    }
}
