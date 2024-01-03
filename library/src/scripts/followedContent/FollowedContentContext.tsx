/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useContext, useState, useEffect } from "react";
import { IUser } from "@library/@types/api/users";
import { ICategory, ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { CategorySortOption } from "@dashboard/@types/api/category";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";

export interface IFollowedContent extends ICategory {
    dateFollowed: string;
    preferences: ICategoryPreferences;
    lastPost: IDiscussion;
}
interface IFollowedContentContext {
    userID: number;
    followedCategories: IFollowedContent[] | null;
    sortBy: string;
    setSortBy: (value) => void;
    error: IError | null;
}

export const FollowedContentContext = React.createContext<IFollowedContentContext>({
    userID: 1,
    followedCategories: null,
    sortBy: CategorySortOption.RECENTLY_FOLLOWED,
    setSortBy: () => {},
    error: null,
});

export function FollowedContentProvider(props: { userID: IUser["userID"]; children: ReactNode }) {
    const { userID, children } = props;
    const [sortBy, setSortBy] = useState(CategorySortOption.RECENTLY_FOLLOWED);
    const [followedCategories, setFollowedCategories] = useState<IFollowedContent[] | null>(null);
    const [error, setError] = useState<IError | null>(null);

    // Make API call to get followed categories,
    useEffect(() => {
        const fetchFollowedCategories = async () => {
            try {
                const { data } = await apiv2.get<IFollowedContent[]>(
                    `categories?followed=1&expand=lastPost,preferences&outputFormat=flat&sort=${sortBy}`,
                );
                setFollowedCategories(data);
            } catch (error) {
                setError(error);
            }
        };

        fetchFollowedCategories();
    }, [sortBy]);

    return (
        <FollowedContentContext.Provider
            value={{
                userID,
                followedCategories,
                sortBy,
                setSortBy,
                error,
            }}
        >
            {children}
        </FollowedContentContext.Provider>
    );
}

export function useFollowedContent() {
    return useContext(FollowedContentContext);
}
