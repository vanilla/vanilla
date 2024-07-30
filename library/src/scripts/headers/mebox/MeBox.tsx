/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IMe } from "@library/@types/api/users";
import { meBoxClasses } from "@library/headers/mebox/pieces/meBoxStyles";
import MessagesDropDown from "@library/headers/mebox/pieces/MessagesDropDown";
import NotificationsDropDown from "@library/headers/mebox/pieces/NotificationsDropDown";
import UserDropdown from "@library/headers/mebox/pieces/UserDropdown";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
export interface IMeBoxProps {
    currentUser?: IMe;
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
        const userInfo = this.props.currentUser;
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
                    <MessagesDropDown count={userInfo.countUnreadConversations} />
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
