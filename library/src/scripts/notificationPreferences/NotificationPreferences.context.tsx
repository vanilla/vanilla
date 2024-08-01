/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, ILoadable } from "@library/@types/api/core";
import { IError } from "@library/errorPages/CoreErrorMessages";
import {
    INotificationPreferences,
    INotificationPreferencesApi,
    NotificationPreferencesSchemaType,
} from "@library/notificationPreferences";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { RecordID } from "@vanilla/utils";
import React, { PropsWithChildren, createContext, useContext } from "react";
import { queryResultToILoadable } from "@library/ReactQueryUtils";
export interface INotificationPreferencesContextValue {
    schema: ILoadable<NotificationPreferencesSchemaType, IError> | null;
    preferences: ILoadable<INotificationPreferences, IError> | null;
    editPreferences: (
        preferences: INotificationPreferences,
        options?: {
            onSuccess?: (data: INotificationPreferences) => void;
            onError?: (error: Error) => void;
        },
    ) => Promise<INotificationPreferences>;
    patchLanguage: (language: string) => Promise<INotificationPreferences>;
}

export const NotificationPreferencesContext = createContext<INotificationPreferencesContextValue>({
    schema: null,
    preferences: null,
    editPreferences: async function (_preferences) {
        return {};
    },
    patchLanguage: async function (_language) {
        return {};
    },
});

export function useNotificationPreferencesContext() {
    return useContext(NotificationPreferencesContext);
}

export function NotificationPreferencesContextProvider(
    props: PropsWithChildren<{
        userID: RecordID;
        api: INotificationPreferencesApi;
    }>,
) {
    const { userID, api } = props;

    const queryClient = useQueryClient();

    const schemaQuery = useQuery<unknown, IApiError, NotificationPreferencesSchemaType>({
        queryFn: async () => await api.getSchema({}),
        queryKey: ["notificationPreferencesSchema"],
    });

    const preferencesQuery = useQuery<unknown, IApiError, INotificationPreferences>({
        queryFn: async () => await api.getUserPreferences({ userID }),
        queryKey: ["userNotificationPreferences", { userID }],
    });

    const editPreferencesMutation = useMutation({
        mutationFn: async (preferences: INotificationPreferences) => {
            return await api.patchUserPreferences({ preferences, userID });
        },

        onMutate: async function (newData) {
            const previousNotificationPreferences = queryClient.getQueryData<INotificationPreferences>([
                "userNotificationPreferences",
                { userID },
            ]);
            queryClient.setQueryData<INotificationPreferences>(["userNotificationPreferences", { userID }], {
                ...previousNotificationPreferences,
                ...newData,
            });

            return { previousNotificationPreferences };
        },

        onError: (_err, _variables, context) => {
            if (context?.previousNotificationPreferences) {
                queryClient.setQueryData<INotificationPreferences>(
                    ["userNotificationPreferences", { userID }],
                    context.previousNotificationPreferences,
                );
            }
        },
        mutationKey: ["editUserNotificationPreferences", { userID }],
    });

    const schema: INotificationPreferencesContextValue["schema"] = queryResultToILoadable(schemaQuery);
    const preferences: INotificationPreferencesContextValue["preferences"] = queryResultToILoadable(preferencesQuery);

    async function patchLanguage(language: string) {
        return await editPreferencesMutation.mutateAsync({
            ...(preferencesQuery.data ?? {}),
            ...({ NotificationLanguage: language } as any),
        });
    }

    return (
        <NotificationPreferencesContext.Provider
            value={{
                ...{
                    schema,
                    preferences,
                    editPreferences: editPreferencesMutation.mutateAsync,
                    patchLanguage,
                },
            }}
        >
            {props.children}
        </NotificationPreferencesContext.Provider>
    );
}
