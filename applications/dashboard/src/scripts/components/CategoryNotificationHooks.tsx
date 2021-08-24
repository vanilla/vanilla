/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useAsync } from "@vanilla/react-utils";

export interface IFollowedCategory {
    name: string;
    categoryID: number;
    preferences: ICategoryPreferences;
    url: string;
}

async function getUserCategoryPreferences(userID: IUser["userID"]) {
    const { data } = await apiv2.get<IFollowedCategory[]>(`/categories/preferences/${userID}`);
    return data;
}

export function useCategoryNotificationPreferences(userID: number) {
    const preferences = useAsync(async () => {
        const prefs = await getUserCategoryPreferences(userID);
        return prefs;
    }, [userID]);

    return preferences;
}
