/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { IPatchUserParams, IPostUserParams } from "@library/features/users/UserActions";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";

export interface IGetUsersParams {
    query?: IUser["name"] | IUser["email"];
    name?: string;
    email?: string;
    roles?: ICategoryPreferences;
    limit?: number;
    page?: number;
}

export interface IGetUsersResponse {
    users: IUser[];
    countUsers: string;
    currentPage: string;
}

export async function getUsers(params: IGetUsersParams = {}) {
    const { data, headers } = await apiv2.get<IUser[]>("/users?expand=profileFields", {
        params: {
            limit: 30,
            ...params,
        },
    });

    return { users: data, countUsers: headers["x-app-page-result-count"], currentPage: headers["x-app-page-current"] };
}

export function useGetUsers(query: IGetUsersParams) {
    const { refetch, isLoading, error, isSuccess, isFetching, data } = useQuery<any, IError, IGetUsersResponse>({
        queryFn: async () => getUsers(query),
        queryKey: ["users_userManagement", query],
        refetchOnMount: "always",
        keepPreviousData: true,
    });

    return { refetch, isLoading, error, isSuccess, isFetching, data };
}

export function useAddUser() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationKey: ["addUser_userManagement"],
        mutationFn: async (params: IPostUserParams) => {
            const response = await apiv2.post(`/users`, params);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries();
        },
        onError: (error) => {
            throw error;
        },
    });
}

export function useUpdateUser(userID: IUser["userID"]) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationKey: ["updateUser_userManagement", userID],
        mutationFn: async (params: IPatchUserParams) => {
            const response = await apiv2.patch(`/users/${userID}`, params);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries();
        },
        onError: (error) => {
            throw error;
        },
    });
}
