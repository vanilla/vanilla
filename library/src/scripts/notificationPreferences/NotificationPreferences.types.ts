/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import { JSONSchemaType } from "@vanilla/json-schema-forms";

export type NotificationPreferencesSchemaType = JSONSchemaType<{
    [key: string]: any | undefined;
}>;

export interface INotificationPreference {
    email?: boolean;
    popup?: boolean;
}

export interface INotificationPreferences {
    [key: string]: INotificationPreference;
}

interface IGetSchemaParams {}

interface IGetUserPreferencesParams {
    userID: IUser["userID"];
}

interface IPatchUserPreferencesParams {
    userID: IUser["userID"];
    preferences: INotificationPreferences;
}

export interface INotificationPreferencesApi {
    getSchema: (params: IGetSchemaParams) => Promise<NotificationPreferencesSchemaType>;
    getUserPreferences: (params: IGetUserPreferencesParams) => Promise<INotificationPreferences>;
    patchUserPreferences: (params: IPatchUserPreferencesParams) => Promise<INotificationPreferences>;
}
