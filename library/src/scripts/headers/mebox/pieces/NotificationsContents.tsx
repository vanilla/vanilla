/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "../../../@types/api/core";
import apiv2 from "../../../apiv2";
import { t } from "../../../dom/appUtils";
import Button from "../../../forms/Button";
import Frame from "../../../layout/frame/Frame";
import FrameBody from "../../../layout/frame/FrameBody";
import FrameFooter from "../../../layout/frame/FrameFooter";
import FrameHeaderWithAction from "../../../layout/frame/FrameHeaderWithAction";
import FramePanel from "../../../layout/frame/FramePanel";
import Loader from "../../../loaders/Loader";
import { settings } from "../../../icons/header";
import LinkAsButton from "../../../routing/LinkAsButton";
import { IMeBoxNotificationItem, MeBoxItemType } from "library/src/scripts/headers/mebox/pieces/MeBoxDropDownItem";
import NotificationsActions from "../../../features/notifications/NotificationsActions";
import { INotificationsStoreState } from "../../../features/notifications/NotificationsModel";
import classNames from "classnames";
import * as React from "react";
import { connect } from "react-redux";
import MeBoxDropDownItemList from "./MeBoxDropDownItemList";
import { withDevice } from "../../../layout/DeviceContext";
import { IDeviceProps, Devices } from "../../../layout/DeviceChecker";
import { loaderClasses } from "../../../loaders/loaderStyles";
import { frameFooterClasses } from "../../../layout/frame/frameStyles";
import { ButtonTypes, buttonUtilityClasses } from "@library/styles/buttonStyles";

export interface INotificationsProps {
    countClass?: string;
    panelBodyClass?: string;
}

/**
 * Implements Notifications Contents to be included in drop down or tabs
 */
export class NotificationsContents extends React.Component<IProps> {
    public render() {
        const { userSlug } = this.props;
        const title = t("Notifications");
        const classesFrameFooter = frameFooterClasses();
        const buttonUtils = buttonUtilityClasses();

        return (
            <Frame className={this.props.className}>
                <FrameHeaderWithAction className="hasAction" title={title}>
                    <LinkAsButton
                        title={t("Notification Preferences")}
                        baseClass={ButtonTypes.ICON}
                        className={classNames(buttonUtils.pushRight)}
                        to={`/profile/preferences/${userSlug}`}
                    >
                        {settings()}
                    </LinkAsButton>
                </FrameHeaderWithAction>
                <FrameBody className={classNames("isSelfPadded", this.props.panelBodyClass)}>
                    <FramePanel>{this.bodyContent()}</FramePanel>
                </FrameBody>
                <FrameFooter>
                    <LinkAsButton
                        className={classNames(buttonUtils.pushLeft)}
                        to={"/profile/notifications"}
                        baseClass={ButtonTypes.TEXT}
                    >
                        {t("All Notifications")}
                    </LinkAsButton>
                    <Button
                        onClick={this.markAllRead}
                        baseClass={ButtonTypes.TEXT}
                        className={classNames("frameFooter-markRead", classesFrameFooter.markRead)}
                    >
                        {t("Mark All Read")}
                    </Button>
                </FrameFooter>
            </Frame>
        );
    }

    /**
     * Get content for the main body panel.
     */
    private bodyContent(): JSX.Element {
        const { notifications } = this.props;

        if (notifications.status !== LoadStatus.SUCCESS || !notifications.data) {
            // This is the height that it happens to be right now.
            // This will be calculated better once we finish the CSS in JS transition.
            const height = this.props.device === Devices.MOBILE ? 80 : 69;
            const loaders = loaderClasses();
            return <Loader loaderStyleClass={loaders.smallLoader} height={height} minimumTime={0} padding={10} />;
        }

        return (
            <MeBoxDropDownItemList
                emptyMessage={t("You do not have any notifications yet.")}
                className="headerDropDown-notifications"
                type={MeBoxItemType.NOTIFICATION}
                data={Object.values(notifications.data)}
            />
        );
    }

    public componentDidMount() {
        const { requestData, notifications } = this.props;

        if (notifications.status === LoadStatus.PENDING) {
            void requestData();
        }
    }

    /**
     * Mark all of the current user's notifications as read, then refresh the store of notifications.
     */
    private markAllRead = () => {
        void this.props.markAllRead();
    };
}

// For clarity, I'm adding className separately because both the container and the content have className, but it's not applied to the same element.
interface IOwnProps extends INotificationsProps, IDeviceProps {
    className?: string;
    userSlug: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

/**
 * Create action creators on the component, bound to a Redux dispatch function.
 *
 * @param dispatch Redux dispatch function.
 */
function mapDispatchToProps(dispatch) {
    const notificationActions = new NotificationsActions(dispatch, apiv2);
    return {
        markAllRead: notificationActions.markAllRead,
        requestData: notificationActions.getNotifications,
    };
}

/**
 * Update the component state, based on changes to the Redux store.
 *
 * @param state Current Redux store state.
 */
function mapStateToProps(state: INotificationsStoreState) {
    const { notificationsByID } = state.notifications;
    const notifications: ILoadable<IMeBoxNotificationItem[]> = {
        ...notificationsByID,
        data:
            notificationsByID.status === LoadStatus.SUCCESS && notificationsByID.data
                ? Object.values(notificationsByID.data).map(notification => {
                      return {
                          message: notification.body,
                          photo: notification.photoUrl || null,
                          to: notification.url,
                          recordID: notification.notificationID,
                          timestamp: notification.dateUpdated,
                          unread: !notification.read,
                          type: MeBoxItemType.NOTIFICATION,
                      } as IMeBoxNotificationItem;
                  })
                : undefined,
    };

    if (notifications.data) {
        // Notifications are likely sorted by ID. Make sure they're sorted by the date they were created, in descending order.
        notifications.data.sort((a: IMeBoxNotificationItem, b: IMeBoxNotificationItem) => {
            const dateA = new Date(a.timestamp).getTime();
            const dateB = new Date(b.timestamp).getTime();
            return dateB - dateA;
        });
    }

    return {
        notifications,
    };
}

// Connect Redux to the React component.
export default withDevice(
    connect(
        mapStateToProps,
        mapDispatchToProps,
    )(NotificationsContents),
);
