/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { JSONSchemaType } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import type { TableInstance } from "react-table";

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

export interface NotificationType {
    /**
     * The phrase returned should be translatable; it will be interpolated into a sentence like "Notify users of `${getDescription()}`".
     * When adding a new category notification type, make sure to add a the phrase to the locales file.
     */
    getDescription(): string;

    /**
     * Return the default preferences for this category type.
     * When available, the user's global notification preferences will be passed as an argument.
     * Implementations should default to false for all properties.
     */
    getDefaultPreferences?(
        userPreferences?: INotificationPreferences,
    ): Partial<ICategoryPreferences> | Record<string, string>;
}
