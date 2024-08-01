/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IRole } from "@dashboard/roles/roleTypes";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { IPatchUserParams, IPostUserParams } from "@library/features/users/UserActions";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { UserSortParams, USERS_LIMIT_PER_PAGE } from "@dashboard/users/userManagement/UserManagementUtils";
import { IApiError, IFieldError } from "@library/@types/api/core";
export interface IGetUsersQueryParams {
    userID?: Array<IUser["userID"]>;
    query?: string;
    name?: string;
    email?: string;
    emailDomain?: string[];
    emailConfirmed?: boolean;
    limit?: number;
    page?: number;
    sort?: UserSortParams;
    roleIDs?: Array<IRole["roleID"]>;
    rankIDs?: number[];
    dateInserted?: string;
    dateLastActive?: string;
    ipAddresses?: string[];
    profileFields?: {
        [key: string]: any;
    };
}

export interface IGetUsersResponse {
    users: IUser[];
    countUsers: string;
    currentPage: string;
}

export async function getUsers(params: IGetUsersQueryParams = {}) {
    const { data, headers } = await apiv2.get<IUser[]>("/users?expand=profileFields", {
        params: {
            limit: USERS_LIMIT_PER_PAGE,
            ...params,
        },
    });

    return {
        users: data,
        countUsers: headers["x-app-page-result-count"],
        currentPage: headers["x-app-page-current"],
    };
}

export function useGetUsers(query: IGetUsersQueryParams, shouldFetch = true) {
    const {
        refetch,
        isLoading,
        error: apiError,
        isSuccess,
        isFetching,
        data,
    } = useQuery<any, IApiError, IGetUsersResponse>({
        queryFn: async () => getUsers(query),
        queryKey: ["users_userManagement", query],
        refetchOnMount: "always",
        keepPreviousData: true,
        refetchOnWindowFocus: false,
        enabled: shouldFetch,
    });

    //send back the first error message
    let error: IApiError | IError | IFieldError | null = apiError;
    const errorsByName = apiError && apiError.errors && Object.keys(apiError.errors);
    if (errorsByName?.length) {
        error = apiError?.errors?.[errorsByName[0]]?.[0] as IError | IFieldError;
    }

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
