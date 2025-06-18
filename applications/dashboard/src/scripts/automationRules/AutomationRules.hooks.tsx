/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError } from "@library/@types/api/core";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import {
    AddEditAutomationRuleParams,
    AutomationRuleActionType,
    AutomationRuleStatusType,
    AutomationRulesAdditionalDataQuery,
    IAutomationRule,
    IAutomationRuleAction,
    IAutomationRuleDispatch,
    IAutomationRulesCatalog,
    IGetAutomationRuleDispatchesParams,
} from "@dashboard/automationRules/AutomationRules.types";
import { IGetCategoryListResponse, useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import { useEffect } from "react";
import { useTagList } from "@library/features/tags/TagsHooks";
import { ITag } from "@library/features/tags/TagsReducer";
import { AxiosResponseHeaders } from "axios";
import { IGetUsersResponse, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";

export function useRecipes(continiousFetch: boolean = true, escalationActionsFilter?: boolean) {
    const queryParams = escalationActionsFilter ? { escalations: true } : {};
    const {
        data: recipes,
        isLoading,
        error,
        isRefetching,
    } = useQuery<any, IApiError, IAutomationRule[]>({
        queryFn: async () => {
            const response = await apiv2.get("/automation-rules/recipes", {
                params: { ...queryParams, expand: "all" },
            });
            return response.data;
        },
        refetchOnMount: "always",
        refetchInterval: continiousFetch ? 15000 : false,
        queryKey: ["automationRules", queryParams],
    });

    return { recipes, isLoading, error, isRefetching };
}

export function useRecipe(automationRuleID: IAutomationRule["automationRuleID"], shouldFetch: boolean = true) {
    const { data: recipe, isLoading } = useQuery<any, IApiError, IAutomationRule>({
        queryFn: async () => {
            const response = await apiv2.get(`/automation-rules/${automationRuleID}/recipe?expand=all`);
            return response.data;
        },
        queryKey: ["automationRule", automationRuleID],
        enabled: shouldFetch,
        refetchOnMount: "always",
    });

    return { recipe, isLoading };
}

export function useAddRecipe() {
    const queryClient = useQueryClient();
    return useMutation<IAutomationRule, IApiError, AddEditAutomationRuleParams>({
        mutationKey: ["add_automationRule"],
        mutationFn: async (params: AddEditAutomationRuleParams): Promise<IAutomationRule> => {
            const { data } = await apiv2.post<IAutomationRule>("/automation-rules", params);
            return data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["automationRules"]);
        },
    });
}

export function useUpdateRecipe(automationRuleID: AddEditAutomationRuleParams["automationRuleID"]) {
    const queryClient = useQueryClient();
    return useMutation<IAutomationRule, IApiError, AddEditAutomationRuleParams>({
        mutationFn: async (params: AddEditAutomationRuleParams) => {
            const response = await apiv2.patch(`/automation-rules/${automationRuleID}`, params);
            return response.data;
        },
        mutationKey: ["update_automationRule", automationRuleID],
        onSuccess: () => {
            void queryClient.invalidateQueries(["automationRules"]);
        },
    });
}

export function useUpdateRecipeStatus(automationRuleID: AddEditAutomationRuleParams["automationRuleID"]) {
    const queryClient = useQueryClient();
    return useMutation<IAutomationRule, IApiError, { status: AutomationRuleStatusType }>({
        mutationFn: async (params: { status: AutomationRuleStatusType }) => {
            const response = await apiv2.put(`/automation-rules/${automationRuleID}/status`, params);
            return response.data;
        },
        mutationKey: ["update_automationRule", automationRuleID],
        onSuccess: () => {
            void queryClient.invalidateQueries(["automationRule", automationRuleID]);
        },
    });
}

export function useDeleteRecipe(automationRuleID: AddEditAutomationRuleParams["automationRuleID"]) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async () => {
            const response = await apiv2.delete(`/automation-rules/${automationRuleID}`);
            return response.data;
        },
        mutationKey: ["delete_automationRule", automationRuleID],
        onSuccess: () => {
            void queryClient.invalidateQueries(["automationRules"]);
        },
    });
}

export function useRunAutomationRule(automationRuleID: AddEditAutomationRuleParams["automationRuleID"]) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationKey: ["run_automationRule", automationRuleID],
        mutationFn: async () => {
            const { data } = await apiv2.post(`/automation-rules/${automationRuleID}/trigger`);
            return data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries(["automationRule", automationRuleID]);
        },
    });
}

export function useAutomationRulesCatalog(escalationActionsFilter?: boolean) {
    const queryParams = escalationActionsFilter ? { escalations: true } : {};
    const { data, isLoading } = useQuery<any, IApiError, IAutomationRulesCatalog>({
        queryFn: async () => {
            const response = await apiv2.get("/automation-rules/catalog", {
                params: queryParams,
            });
            return response.data;
        },
        queryKey: ["automationRulesCatalog", queryParams],
    });

    return { data, isLoading };
}

export function useAutomationRulesDispatches(queryParams: IGetAutomationRuleDispatchesParams) {
    const { data, isFetching, error } = useQuery<
        any,
        IApiError,
        { dispatches: IAutomationRuleDispatch[]; headers: AxiosResponseHeaders }
    >({
        queryFn: async () => {
            const response = await apiv2.get("/automation-rules/dispatches?expand[]=all", {
                params: { ...queryParams },
            });
            return { dispatches: response.data, headers: response.headers };
        },
        queryKey: ["automationRules_dispatches", queryParams],
        keepPreviousData: true,
    });

    return {
        dispatches: data?.dispatches ?? [],
        countDispatches: data?.headers["x-app-page-result-count"],
        currentPage: data?.headers["x-app-page-current"],
        isFetching,
        error,
    };
}

export function useGetAdditionalData(query: AutomationRulesAdditionalDataQuery, initialQuery: any) {
    const queryClient = useQueryClient();

    const { data: additionalCategories } = useCategoryList(
        query?.categoriesQuery ?? {},
        Array.isArray(query?.categoriesQuery?.categoryID)
            ? Boolean(query?.categoriesQuery?.categoryID?.length)
            : !!query?.categoriesQuery?.categoryID,
    );

    const { data: additionalTags } = useTagList(query?.tagsQuery ?? {}, Boolean(query?.tagsQuery?.tagID?.length));

    const { data: additionalUsersData } = useGetUsers(
        query?.usersQuery ?? {},
        Boolean(query?.usersQuery?.userID?.length),
    );

    useEffect(() => {
        if (additionalCategories) {
            queryClient.setQueryData(["categoryList", initialQuery], (initialData: IGetCategoryListResponse) => {
                return {
                    ...initialData,
                    result: [...initialData?.result, ...additionalCategories.result],
                };
            });
        }
        if (additionalTags) {
            queryClient.setQueryData(["tags", initialQuery], (initialData: ITag[]) => {
                return [...initialData, ...additionalTags];
            });
        }
        if (additionalUsersData) {
            queryClient.setQueryData(["users_userManagement", initialQuery], (initialData: IGetUsersResponse) => {
                return {
                    ...initialData,
                    users: [...initialData?.users, ...additionalUsersData.users],
                };
            });
        }
    }, [additionalCategories, additionalTags, additionalUsersData]);
}

export function useGetActionDynamicSchema(queryParams: { actionType?: AutomationRuleActionType; params?: any } | null) {
    const { data, isFetching } = useQuery<any, IApiError, IAutomationRuleAction>({
        queryFn: async () => {
            const response = await apiv2.get(`/automation-rules/action-by-type?type=${queryParams?.actionType}`, {
                params: queryParams?.params,
            });
            return response.data;
        },
        queryKey: ["automationRules_action_dynamicSchema", queryParams?.actionType, queryParams?.params],
        enabled: Boolean(queryParams),
        staleTime: 0,
    });

    return { data, isFetching };
}
