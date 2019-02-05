/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { INotification } from "@library/@types/api/notifications";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import Frame from "@library/components/frame/Frame";
import FrameBody from "@library/components/frame/FrameBody";
import FrameFooter from "@library/components/frame/FrameFooter";
import FrameHeaderWithAction from "@library/components/frame/FrameHeaderWithAction";
import FramePanel from "@library/components/frame/FramePanel";
import { settings } from "@library/components/icons/header";
import LinkAsButton from "@library/components/LinkAsButton";
import { IMeBoxNotificationItem, MeBoxItemType } from "@library/components/mebox/pieces/MeBoxDropDownItem";
import NotificationsActions from "@library/notifications/NotificationsActions";
import { INotificationsStoreState, IWithNotifications } from "@library/notifications/NotificationsModel";
import classNames from "classnames";
import * as React from "react";
import { connect } from "react-redux";
import MeBoxDropDownItemList from "./MeBoxDropDownItemList";

export interface INotificationsProps {
    countClass?: string;
    panelBodyClass?: string;
    markAllRead?: () => void;
}

// For clarity, I'm adding className separately because both the container and the content have className, but it's not applied to the same element.
interface IProps extends INotificationsProps, IWithNotifications {
    notificationsActions: NotificationsActions;
    className?: string;
    preferencesUrl: string;
}

/**
 * Implements Notifications Contents to be included in drop down or tabs
 */
export class NotificationsContents extends React.Component<IProps> {
    public render() {
        const { notificationsByID, preferencesUrl } = this.props;
        const title = t("Notifications");
        const data: IMeBoxNotificationItem[] = [];

        if (notificationsByID.status === LoadStatus.SUCCESS && notificationsByID.data) {
            for (const notification of Object.values(notificationsByID.data) as INotification[]) {
                data.push({
                    message: notification.body,
                    photo: notification.photoUrl || null,
                    to: notification.url,
                    recordID: notification.notificationID,
                    timestamp: notification.dateUpdated,
                    unread: !notification.read,
                    type: MeBoxItemType.NOTIFICATION,
                });
            }
        }

        return (
            <Frame className={this.props.className}>
                <FrameHeaderWithAction className="hasAction" title={title}>
                    <LinkAsButton
                        title={t("Notification Preferences")}
                        className="headerDropDown-headerButton headerDropDown-notifications button-pushRight"
                        to={preferencesUrl}
                        baseClass={ButtonBaseClass.ICON}
                    >
                        {settings()}
                    </LinkAsButton>
                </FrameHeaderWithAction>
                <FrameBody className={classNames("isSelfPadded", this.props.panelBodyClass)}>
                    <FramePanel>
                        <MeBoxDropDownItemList
                            emptyMessage={t("You do not have any notifications yet.")}
                            className="headerDropDown-notifications"
                            type={MeBoxItemType.NOTIFICATION}
                            data={data}
                        />
                    </FramePanel>
                </FrameBody>
                <FrameFooter>
                    <LinkAsButton
                        className="headerDropDown-footerButton frameFooter-allButton button-pushLeft"
                        to={"/profile/notifications"}
                        baseClass={ButtonBaseClass.TEXT}
                    >
                        {t("All Notifications")}
                    </LinkAsButton>
                    <Button
                        onClick={this.markAllRead}
                        baseClass={ButtonBaseClass.TEXT}
                        className="frameFooter-markRead"
                    >
                        {t("Mark All Read")}
                    </Button>
                </FrameFooter>
            </Frame>
        );
    }

    public componentDidMount() {
        const { notificationsActions, notificationsByID } = this.props;

        if (notificationsByID.status === LoadStatus.PENDING) {
            void notificationsActions.getNotifications();
        }
    }

    /**
     * Mark all of the current user's notifications as read, then refresh the store of notifications.
     */
    private markAllRead = async () => {
        const { notificationsActions } = this.props;
        await notificationsActions.markAllRead();
        void notificationsActions.getNotifications();
    };
}

/**
 * Create action creators on the component, bound to a Redux dispatch function.
 *
 * @param dispatch Redux dispatch function.
 */
function mapDispatchToProps(dispatch) {
    return {
        notificationsActions: new NotificationsActions(dispatch, apiv2),
    };
}

/**
 * Update the component state, based on changes to the Redux store.
 *
 * @param state Current Redux store state.
 */
function mapStateToProps(state: INotificationsStoreState) {
    const { notificationsByID } = state.notifications;
    return {
        notificationsByID,
    };
}

// Connect Redux to the React component.
export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(NotificationsContents);
