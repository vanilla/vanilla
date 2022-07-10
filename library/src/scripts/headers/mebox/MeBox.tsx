/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import UserDropdown from "@library/headers/mebox/pieces/UserDropdown";
import MessagesDropDown from "@library/headers/mebox/pieces/MessagesDropDown";
import { IInjectableUserState } from "@library/features/users/userTypes";
import NotificationsDropDown from "@library/headers/mebox/pieces/NotificationsDropDown";
import { t } from "@library/utility/appUtils";
export interface IMeBoxProps extends IInjectableUserState {
    countClass?: string;
    className?: string;
    countsClass?: string;
    buttonClassName?: string;
    contentClassName?: string;
    withSeparator?: boolean;
    withLabel?: boolean;
}

/**
 * Implements MeBox component. Note that on mobile we use the CompactMeBox component but they share children components
 */
export default class MeBox extends React.Component<IMeBoxProps> {
    public render() {
        const userInfo = this.props.currentUser.data;
        if (!userInfo) {
            return null;
        }
        const classes = meBoxClasses();
        const separator = this.props.withSeparator && <span>|</span>;
        const withLabel = this.props.withLabel;
        return (
            <div className={classNames("meBox", this.props.className, classes.root)}>
                {separator}
                <div className={classes.meboxItem}>
                    <NotificationsDropDown userSlug={userInfo.name} countUnread={userInfo.countUnreadNotifications} />
                    {withLabel && <div className={classes.label}>{t("Notifications")}</div>}
                </div>
                {separator}
                <div className={classes.meboxItem}>
                    <MessagesDropDown />
                    {withLabel && <div className={classes.label}>{t("Messages")}</div>}
                </div>
                {separator}
                <div className={classes.meboxItem}>
                    <UserDropdown />
                    {withLabel && <div className={classes.label}>{t("Profile")}</div>}
                </div>
            </div>
        );
    }
}
