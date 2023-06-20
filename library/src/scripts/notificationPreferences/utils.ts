/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { QueryObserverResult } from "@tanstack/react-query";
import { INotificationPreferences } from "@library/notificationPreferences";
import get from "lodash/get";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { getDeepPropertyListInDotNotation } from "@library/utility/appUtils";

// A utility function to translate react-query's QueryObserverResult type into our more familiar ILoadable type
export function queryResultToILoadable<T = never, E = IApiError>(
    queryResult: QueryObserverResult<T, E>,
): ILoadable<T, E> {
    switch (queryResult.status) {
        case "error":
            return {
                status: LoadStatus.ERROR,
                error: queryResult.error,
            };
        case "success":
            return {
                status: LoadStatus.SUCCESS,
                data: queryResult.data,
            };
        case "loading":
            return {
                status: LoadStatus.LOADING,
            };
        default:
            return {
                status: LoadStatus.PENDING,
            };
    }
}

export function isParentOfNotificationPreferenceSchemas(schema: JsonSchema) {
    return (
        schema.type === "object" &&
        !!schema.properties &&
        Object.values(schema.properties).every((propertySchema: JsonSchema) =>
            isNotificationPreferenceSchema(propertySchema),
        )
    );
}

export function isNotificationPreferenceSchema(schema: JsonSchema) {
    return (
        schema.type === "object" &&
        (("email" in schema.properties && schema.properties.email.type === "boolean") ||
            ("popup" in schema.properties && schema.properties.popup.type === "boolean"))
    );
}

// This outputs an object that will satisfy the nested JsonSchema used on the Notification Preferences page
export function mapNotificationPreferencesToSchemaLikeStructure(
    schema: JsonSchema,
    preferences: INotificationPreferences,
): object {
    function traverseSchema(current = {}, schema: JsonSchema, preferences: INotificationPreferences) {
        let output = { ...current };

        if (schema.properties) {
            Object.entries(schema.properties).forEach(([key, prop]) => {
                const property = prop as JsonSchema;

                output = {
                    ...output,
                    [key]: isNotificationPreferenceSchema(property)
                        ? {
                              ...("email" in property.properties && { email: preferences[key]?.email }),
                              ...("popup" in property.properties && { popup: preferences[key]?.popup }),
                          }
                        : traverseSchema({}, property, preferences),
                };
            });
        }

        return output;
    }

    return traverseSchema({}, schema, preferences);
}

// This translates the Notification Preferences form values from a nested structure (which matches the schema) into a flatter structure (expected by the PATCH endpoint)
export function mapNestedFormValuesToNotificationPreferences(nestedFormValues: object): INotificationPreferences {
    const uniqueKeyPaths = getDeepPropertyListInDotNotation(nestedFormValues).filter(
        (path) => path.endsWith(".email") || path.endsWith(".popup"),
    );

    const activityKeyPaths = Array.from(
        new Set(
            uniqueKeyPaths.map((path) => {
                return path.slice(0, path.endsWith(".email") ? path.lastIndexOf(".email") : path.lastIndexOf(".popup"));
            }),
        ),
    );

    const values: INotificationPreferences = Object.fromEntries(
        activityKeyPaths.map((path) => {
            const activityName = path.slice(path.lastIndexOf(".") + 1);
            return [
                activityName,
                {
                    ["email"]: !!get(nestedFormValues, `${path}.email`, false),
                    ["popup"]: !!get(nestedFormValues, `${path}.popup`, false),
                },
            ];
        }),
    );

    return values;
}
