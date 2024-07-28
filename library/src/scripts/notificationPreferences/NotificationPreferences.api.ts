/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import {
    INotificationPreferences,
    INotificationPreferencesApi,
    NotificationPreferencesSchemaType,
} from "@library/notificationPreferences";

const API_ENDPOINT = "/notification-preferences";

const NotificationPreferencesApi: INotificationPreferencesApi = {
    getSchema: async function (params) {
        const response = apiv2.get<NotificationPreferencesSchemaType>(`${API_ENDPOINT}/schema`, params);

        return (await response).data;
    },
    getUserPreferences: async function ({ userID, ...params }) {
        const response = apiv2.get<INotificationPreferences>(`${API_ENDPOINT}/${userID}`, params);
        return (await response).data;
    },
    patchUserPreferences: async function ({ userID, preferences }) {
        const response = apiv2.patch<INotificationPreferences>(`${API_ENDPOINT}/${userID}`, {
            ...preferences,
        });
        return (await response).data;
    },
};

export default NotificationPreferencesApi;
