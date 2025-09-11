/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, IServerError } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { IFollowedCategoryNotificationPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { Dispatch } from "react";

export function usePatchCategoryNotificationPreferences(args: {
    categoryID: RecordID;
    userID: IUser["userID"];
    setServerError: Dispatch<IServerError | null>;
}) {
    const { categoryID, userID, setServerError } = args;

    const queryClient = useQueryClient();
    const toast = useToast();

    return useMutation({
        mutationFn: async (newPreferences: IFollowedCategoryNotificationPreferences) => {
            setServerError(null);
            const { data } = await apiv2.patch<IFollowedCategoryNotificationPreferences>(
                `/categories/${categoryID}/preferences/${userID}`,
                newPreferences,
            );

            return data;
        },

        onMutate: async function (newData) {
            setServerError(null);
            const previousPreferences = queryClient.getQueryData<IFollowedCategoryNotificationPreferences>([
                "categoryNotificationPreferences",
                { categoryID, userID },
            ]);
            queryClient.setQueryData<IFollowedCategoryNotificationPreferences>(
                ["categoryNotificationPreferences", { categoryID, userID }],
                newData,
            );

            return { previousPreferences };
        },

        onError: (_err: IServerError, _variables, context) => {
            if (context?.previousPreferences) {
                queryClient.setQueryData<IFollowedCategoryNotificationPreferences>(
                    ["categoryNotificationPreferences", { categoryID, userID }],
                    context.previousPreferences,
                );
                setServerError(_err);
            }
        },

        onSuccess: async (newData) => {
            queryClient.setQueryData<IFollowedCategoryNotificationPreferences>(
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

export function useGetCategoryNotificationPreferences(args: {
    categoryID: RecordID;
    userID: IUser["userID"];
    initialData?: IFollowedCategoryNotificationPreferences;
}) {
    const { categoryID, userID, initialData } = args;

    return useQuery<unknown, IApiError, IFollowedCategoryNotificationPreferences>({
        queryFn: async () =>
            await apiv2.get<IFollowedCategoryNotificationPreferences>(
                `/categories/${categoryID}/preferences/${userID}`,
            ),
        queryKey: ["categoryNotificationPreferences", { categoryID, userID }],
        initialData,
        refetchOnWindowFocus: false,
    });
}
