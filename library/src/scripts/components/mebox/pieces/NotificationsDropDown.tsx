/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import NotificationsContents, { INotificationsProps } from "@library/components/mebox/pieces/NotificationsContents";
import NotificationsToggle from "@library/components/mebox/pieces/NotificationsToggle";
import { connect } from "react-redux";
import { IMeBoxNotificationItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import apiv2 from "@library/apiv2";
import NotificationsActions from "@library/notifications/NotificationsActions";
import { INotificationsStoreState } from "@library/notifications/NotificationsModel";

interface IProps extends INotificationsProps {
    className?: string;
    buttonClassName?: string;
    contentsClassName?: string;
    actions: NotificationsActions;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export class NotificationsDropDown extends React.Component<IProps, IState> {

    private id = uniqueIDFromPrefix("notificationsDropDown");

    public state: IState = {
        open: false,
    };

    public render() {
        return (
            <DropDown
                id={this.id}
                name={t("Notifications")}
                buttonClassName={classNames("vanillaHeader-notifications", this.props.buttonClassName)}
                buttonBaseClass={ButtonBaseClass.CUSTOM}
                renderLeft={true}
                contentsClassName={this.props.contentsClassName}
                buttonContents={
                    <NotificationsToggle
                        count={this.props.data.length}
                        open={this.state.open}
                        countClass={this.props.countClass}
                    />
                }
                onVisibilityChange={this.setOpen}
            >
                <NotificationsContents
                    data={this.props.data}
                    countClass={this.props.countClass}
                    userSlug={this.props.userSlug}
                    markAllRead={this.markAllNotificationsRead}
                />
            </DropDown>
        );
    }

    public componentDidMount() {
        void this.props.actions.getNotifications();
    }

    private markAllNotificationsRead = async () => {
        await this.props.actions.markAllRead();
        void this.props.actions.getNotifications();
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };
}

function mapDispatchToProps(dispatch) {
    return {
        actions: new NotificationsActions(dispatch, apiv2),
    };
}

function mapStateToProps(state: INotificationsStoreState) {
    const data: IMeBoxNotificationItem[] = [];
    const notificationsByID = state.notifications.notificationsByID.data;

    if (notificationsByID) {
        for (const notification of Object.values(notificationsByID)) {
            data.push({
                message: notification.body,
                photo: notification.photoUrl || null,
                to: notification.url,
                recordID: notification.notificationID,
                timestamp: notification.dateInserted,
                unread: !notification.read,
                type: MeBoxItemType.NOTIFICATION,
            });
        }
    }

    return {
        data,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NotificationsDropDown);
