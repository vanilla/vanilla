/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";
import { ILoadable, LoadStatus, INotification } from "@library/@types/api";
import NotificationsActions from "@library/notifications/NotificationsActions";

interface INotificationsState {
    notificationsByID: ILoadable<{ [key: number]: INotification }>;
}

export interface INotificationsStoreState {
    notifications: INotificationsState;
}

export default class NotificationsModel implements ReduxReducer<INotificationsState> {
    public readonly initialState: INotificationsState = {
        notificationsByID: {
            data: {},
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state: INotificationsState = this.initialState,
        action: typeof NotificationsActions.ACTION_TYPES,
    ): INotificationsState => {
        return produce(state, nextState => {
            switch (action.type) {
                case NotificationsActions.GET_NOTIFICATIONS_REQUEST:
                    nextState.notificationsByID.status = LoadStatus.LOADING;
                    break;
                case NotificationsActions.GET_NOTIFICATIONS_RESPONSE:
                    nextState.notificationsByID.status = LoadStatus.SUCCESS;
                    nextState.notificationsByID.data = {};
                    const notifications = action.payload.data as INotification[];
                    notifications.forEach(notification => {
                        nextState.notificationsByID.data![notification.notificationID] = notification;
                    });
                    break;
                case NotificationsActions.GET_NOTIFICATIONS_ERROR:
                    nextState.notificationsByID.status = LoadStatus.ERROR;
                    nextState.notificationsByID.error = action.payload;
                    break;
            }
        });
    };
}
