/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/state/ReduxActions";
import { INotification } from "@library/@types/api";

/**
 * Redux actions for the current user's notification data.
 */
export default class NotificationsActions extends ReduxActions {
    public static readonly GET_NOTIFICATION_REQUEST = "@@notifications/GET_NOTIFICATION_REQUEST";
    public static readonly GET_NOTIFICATION_RESPONSE = "@@notifications/GET_NOTIFICATION_RESPONSE";
    public static readonly GET_NOTIFICATION_ERROR = "@@notifications/GET_NOTIFICATION_ERROR";

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

    public static markReadACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.MARK_READ_REQUEST,
        NotificationsActions.MARK_READ_RESPONSE,
        NotificationsActions.MARK_READ_ERROR,
        {} as INotification,
        {},
    );

    public static readonly MARK_ALL_READ_REQUEST = "@@notifications/MARK_ALL_READ_REQUEST";
    public static readonly MARK_ALL_READ_RESPONSE = "@@notifications/MARK_ALL_READ_RESPONSE";
    public static readonly MARK_ALL_READ_ERROR = "@@notifications/MARK_ALL_READ_ERROR";

    public static markAllReadACs = ReduxActions.generateApiActionCreators(
        NotificationsActions.MARK_ALL_READ_REQUEST,
        NotificationsActions.MARK_ALL_READ_RESPONSE,
        NotificationsActions.MARK_ALL_READ_ERROR,
        {},
        {},
    );

    public static readonly ACTION_TYPES:
        | ActionsUnion<typeof NotificationsActions.getNotificationACs>
        | ActionsUnion<typeof NotificationsActions.getNotificationsACs>
        | ActionsUnion<typeof NotificationsActions.markReadACs>
        | ActionsUnion<typeof NotificationsActions.markAllReadACs>;

    public getNotification(id: number) {
        return this.dispatchApi("get", `/notifications/${id}`, NotificationsActions.getNotificationACs, {});
    }

    public getNotifications() {
        return this.dispatchApi("get", "/notifications", NotificationsActions.getNotificationsACs, {});
    }

    public markRead(id: number) {
        return this.dispatchApi("patch", `/notifications/${id}`, NotificationsActions.markReadACs, {
            read: true,
        });
    }

    public markAllRead() {
        return this.dispatchApi("patch", "/notifications", NotificationsActions.markAllReadACs, {
            read: true,
        });
    }
}
