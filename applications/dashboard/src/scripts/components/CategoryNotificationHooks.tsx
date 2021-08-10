/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useEffect, useState } from "react";

export interface ICategoryPreferences {
    name: string;
    categoryID: number;
    email: boolean;
    popup: boolean;
}

export interface IPatchCategoryParams {
    categoryID: number;
    email: boolean;
    popup: boolean;
}

// the CategoryPreferences schema returned from API
type CategoryPreferences = {
    useEmailNotifications: boolean;
    postNotifications: "follow" | "discussions" | "all";
};

async function patchUserCategoryPreference(userID: IUser["userID"], config: IPatchCategoryParams) {
    const { categoryID, email, popup } = config;
    const { data } = await apiv2.patch<CategoryPreferences>(`/categories/${categoryID}/preferences/${userID}`, {
        postNotifications: popup ? "all" : "follow",
        useEmailNotifications: email,
    });
    return data;
}

async function getUserCategoryPreferences(userID: IUser["userID"]) {
    const { data } = await apiv2.get<
        Array<{
            name: string;
            categoryID: number;
            preferences: CategoryPreferences;
        }>
    >(`/categories/preferences/${userID}`);
    return data;
}

export function useCategoryNotificationPreferences(userID: number) {
    const [preferences, setPreferences] = useState<ILoadable<ICategoryPreferences[]>>({
        status: LoadStatus.LOADING,
        data: [],
    });

    const setCategoryPreference = (config: IPatchCategoryParams) => {
        setPreferences((prevState) => ({
            ...prevState,
            status: LoadStatus.PENDING,
        }));
        patchUserCategoryPreference(userID, config).then(({ useEmailNotifications, postNotifications }) => {
            setPreferences((prevState) => ({
                status: LoadStatus.SUCCESS,
                data: prevState.data?.map((preferences) => ({
                    ...preferences,
                    ...(preferences.categoryID === config.categoryID
                        ? {
                              email: useEmailNotifications,
                              popup: postNotifications !== "follow",
                          }
                        : {}),
                })),
            }));
        });
    };

    // Get the user category preferences when the component mounts & hook is called
    useEffect(() => {
        getUserCategoryPreferences(userID).then((response) => {
            setPreferences({
                status: LoadStatus.SUCCESS,
                data: response.map(
                    ({ name, categoryID, preferences: { postNotifications, useEmailNotifications } }) => ({
                        name,
                        categoryID,
                        email: useEmailNotifications,
                        popup: postNotifications !== "follow",
                    }),
                ),
            });
        });
    }, [userID]);

    return {
        preferences: preferences.data ?? [],
        isLoading: preferences.status === LoadStatus.LOADING,
        setCategoryPreference,
    };
}
