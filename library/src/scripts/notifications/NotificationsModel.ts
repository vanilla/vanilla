/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxReducer from "@library/state/ReduxReducer";
import produce from "immer";
import { ILoadable, LoadStatus } from "@library/@types/api";
import NotificationsActions from "@library/notifications/NotificationsActions";
import { INotification } from "@library/@types/api";

interface INotificationsState {
    notificationsByID: ILoadable<{ [key: number]: INotification }>;
}

export default class UsersModel implements ReduxReducer<INotificationsState> {
    public readonly initialState: INotificationsState = {
        notificationsByID: {
            data: [],
            status: LoadStatus.PENDING,
        },
    };

    public reducer = (
        state: INotificationsState = this.initialState,
        action: typeof NotificationsActions.ACTION_TYPES,
    ): INotificationsState => {
        return produce(state, draft => {
            switch (action.type) {
                case NotificationsActions.GET_NOTIFICATIONS_REQUEST:
                    draft.notificationsByID.status = LoadStatus.LOADING;
                    break;
                case NotificationsActions.GET_NOTIFICATIONS_RESPONSE:
                    draft.notificationsByID.status = LoadStatus.SUCCESS;
                    draft.notificationsByID.data = {};
                    const notifications = action.payload.data as INotification[];
                    notifications.forEach(notification => {
                        draft.notificationsByID.data![notification.notificationID] = notification;
                    });
                    break;
                case NotificationsActions.GET_NOTIFICATIONS_ERROR:
                    draft.notificationsByID.status = LoadStatus.ERROR;
                    draft.notificationsByID.error = action.payload;
                    break;
            }
        });
    };
}
