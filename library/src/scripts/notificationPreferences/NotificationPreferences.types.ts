/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JSONSchemaType } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import { TableInstance } from "react-table";

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
    userID: RecordID;
}

interface IPatchUserPreferencesParams {
    userID: RecordID;
    preferences: INotificationPreferences;
}

export interface INotificationPreferencesApi {
    getSchema: (params: IGetSchemaParams) => Promise<NotificationPreferencesSchemaType>;
    getUserPreferences: (params: IGetUserPreferencesParams) => Promise<INotificationPreferences>;
    patchUserPreferences: (params: IPatchUserPreferencesParams) => Promise<INotificationPreferences>;
}

export type ColumnType = { popup?: boolean; email?: boolean; description: string; id: string; error?: boolean };
export type TableType = TableInstance<ColumnType>;
