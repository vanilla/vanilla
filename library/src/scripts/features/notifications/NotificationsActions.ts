/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { INotification, INotificationWritable } from "@library/@types/api/notifications";

/**
 * Redux actions for the current user's notification data.
 */
export default class NotificationsActions extends ReduxActions {
    public static readonly GET_NOTIFICATION_REQUEST = "@@notifications/GET_NOTIFICATION_REQUEST";
    public static readonly GET_NOTIFICATION_RESPONSE = "@@notifications/GET_NOTIFICATION_RESPONSE";
    public static readonly GET_NOTIFICATION_ERROR = "@@notifications/GET_NOTIFICATION_ERROR";

    /**
     * Action creators for getting a single notification.
     */
    public static getNotificationACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.GET_NOTIFICATION_REQUEST,
        NotificationsActions.GET_NOTIFICATION_RESPONSE,
        NotificationsActions.GET_NOTIFICATION_ERROR,
        {} as INotification,
        {},
    );

    public static readonly GET_NOTIFICATIONS_REQUEST = "@@notifications/GET_NOTIFICATIONS_REQUEST";
    public static readonly GET_NOTIFICATIONS_RESPONSE = "@@notifications/GET_NOTIFICATIONS_RESPONSE";
    public static readonly GET_NOTIFICATIONS_ERROR = "@@notifications/GET_NOTIFICATIONS_ERROR";

    /**
     * Action creators for getting a paginated list of the current user's notifications.
     */
    public static getNotificationsACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.GET_NOTIFICATIONS_REQUEST,
        NotificationsActions.GET_NOTIFICATIONS_RESPONSE,
        NotificationsActions.GET_NOTIFICATIONS_ERROR,
        {} as INotification[],
        {},
    );

    public static readonly MARK_READ_REQUEST = "@@notifications/MARK_READ_REQUEST";
    public static readonly MARK_READ_RESPONSE = "@@notifications/MARK_READ_RESPONSE";
    public static readonly MARK_READ_ERROR = "@@notifications/MARK_READ_ERROR";

    /**
     * Action creators for marking a single notification as read.
     */
    public static markReadACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.MARK_READ_REQUEST,
        NotificationsActions.MARK_READ_RESPONSE,
        NotificationsActions.MARK_READ_ERROR,
        {} as INotification,
        {} as INotificationWritable,
    );

    public static readonly MARK_ALL_READ_REQUEST = "@@notifications/MARK_ALL_READ_REQUEST";
    public static readonly MARK_ALL_READ_RESPONSE = "@@notifications/MARK_ALL_READ_RESPONSE";
    public static readonly MARK_ALL_READ_ERROR = "@@notifications/MARK_ALL_READ_ERROR";

    /**
     * Action creators for marking all of the current user's notifications as read.
     */
    public static markAllReadACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.MARK_ALL_READ_REQUEST,
        NotificationsActions.MARK_ALL_READ_RESPONSE,
        NotificationsActions.MARK_ALL_READ_ERROR,
        {},
        {} as INotificationWritable,
    );

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof NotificationsActions.getNotificationACs>
        | ActionsUnion<typeof NotificationsActions.getNotificationsACs>
        | ActionsUnion<typeof NotificationsActions.markReadACs>
        | ActionsUnion<typeof NotificationsActions.markAllReadACs>;

    /**
     * Get a single notification.
     *
     * @param id Unique ID of the notification.
     */
    public getNotification = (id: number) => {
        return this.dispatchApi("get", `/notifications/${id}`, NotificationsActions.getNotificationACs, {});
    };

    /**
     * Get a paginated list of notifications for the current user.
     */
    public getNotifications = () => {
        return this.dispatchApi("get", "/notifications", NotificationsActions.getNotificationsACs, {});
    };

    /**
     * Mark a single notification as read.
     *
     * @param id Unique ID of the notification.
     */
    public markRead = (id: number) => {
        return this.dispatchApi("patch", `/notifications/${id}`, NotificationsActions.markReadACs, {
            read: true,
        });
    };

    /**
     * Mark all notifications for the current user as read.
     */
    public markAllRead = () => {
        return this.dispatchApi("patch", "/notifications", NotificationsActions.markAllReadACs, {
            read: true,
        });
    };
}
