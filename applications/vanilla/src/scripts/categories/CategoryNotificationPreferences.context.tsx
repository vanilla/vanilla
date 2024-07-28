/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import {
    CategoryNotificationPreferencesContext,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";

function usePatchCategoryNotificationPreferences(args: { categoryID: number; userID: IUser["userID"] }) {
    const { categoryID, userID } = args;

    const queryClient = useQueryClient();

    const toast = useToast();

    return useMutation({
        mutationFn: async (newPreferences: ICategoryPreferences) => {
            const { data } = await apiv2.patch<ICategoryPreferences>(
                `/categories/${categoryID}/preferences/${userID}`,
                newPreferences,
            );

            return data;
        },

        onMutate: async function (newData) {
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

        onError: (_err, _variables, context) => {
            if (context?.previousPreferences) {
                queryClient.setQueryData<ICategoryPreferences>(
                    ["categoryNotificationPreferences", { categoryID, userID }],
                    context.previousPreferences,
                );
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

    const preferencesQuery = useGetCategoryNotificationPreferences({
        categoryID,
        userID,
        initialData: initialPreferences,
    });

    const { mutateAsync } = usePatchCategoryNotificationPreferences({ categoryID, userID });

    return (
        <CategoryNotificationPreferencesContext.Provider
            value={{ preferences: preferencesQuery.data, setPreferences: mutateAsync }}
        >
            {props.children}
        </CategoryNotificationPreferencesContext.Provider>
    );
}
