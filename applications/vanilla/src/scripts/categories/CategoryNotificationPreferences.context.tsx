/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, IServerError } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import {
    CategoryNotificationPreferencesContext,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { IError } from "@library/errorPages/CoreErrorMessages";
import React, { Dispatch, useState } from "react";
import Message from "@library/messages/Message";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";

function usePatchCategoryNotificationPreferences(args: {
    categoryID: number;
    userID: IUser["userID"];
    setServerError: Dispatch<IServerError | null>;
}) {
    const { categoryID, userID, setServerError } = args;

    const queryClient = useQueryClient();
    const toast = useToast();

    return useMutation({
        mutationFn: async (newPreferences: ICategoryPreferences) => {
            setServerError(null);
            const { data } = await apiv2.patch<ICategoryPreferences>(
                `/categories/${categoryID}/preferences/${userID}`,
                newPreferences,
            );

            return data;
        },

        onMutate: async function (newData) {
            setServerError(null);
            const previousPreferences = queryClient.getQueryData<ICategoryPreferences>([
                "categoryNotificationPreferences",
                { categoryID, userID },
            ]);
            queryClient.setQueryData<ICategoryPreferences>(
                ["categoryNotificationPreferences", { categoryID, userID }],
                newData,
            );

            return { previousPreferences };
        },

        onError: (_err: IServerError, _variables, context) => {
            if (context?.previousPreferences) {
                queryClient.setQueryData<ICategoryPreferences>(
                    ["categoryNotificationPreferences", { categoryID, userID }],
                    context.previousPreferences,
                );
                setServerError(_err);
            }
        },

        onSuccess: async (newData) => {
            queryClient.setQueryData<ICategoryPreferences>(
                ["categoryNotificationPreferences", { categoryID, userID }],
                newData,
            );

            toast.addToast({
                autoDismiss: true,
                body: t("Success! Preferences saved."),
            });
        },

        mutationKey: [categoryID, userID],
    });
}

function useGetCategoryNotificationPreferences(args: {
    categoryID: number;
    userID: IUser["userID"];
    initialData?: ICategoryPreferences;
}) {
    const { categoryID, userID, initialData } = args;

    return useQuery<unknown, IApiError, ICategoryPreferences>({
        queryFn: async () => await apiv2.get<ICategoryPreferences>(`/categories/${categoryID}/preferences/${userID}`),
        queryKey: ["categoryNotificationPreferences", { categoryID, userID }],
        initialData,
    });
}

export function CategoryNotificationPreferencesContextProvider(
    props: React.PropsWithChildren<{
        userID: IUser["userID"];
        categoryID: number;
        initialPreferences: ICategoryPreferences;
    }>,
) {
    const { userID, categoryID, initialPreferences } = props;
    const [serverError, setServerError] = useState<IServerError | null>(null);
    const classesFrameBody = frameBodyClasses();
    const preferencesQuery = useGetCategoryNotificationPreferences({
        categoryID,
        userID,
        initialData: initialPreferences,
    });

    const { mutateAsync } = usePatchCategoryNotificationPreferences({
        categoryID,
        userID,
        setServerError,
    });

    return (
        <CategoryNotificationPreferencesContext.Provider
            value={{ preferences: preferencesQuery.data, setPreferences: mutateAsync }}
        >
            {serverError && (
                <Message error={serverError} stringContents={serverError.message} className={classesFrameBody.error} />
            )}
            {props.children}
        </CategoryNotificationPreferencesContext.Provider>
    );
}
