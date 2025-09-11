/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useCustomPageContext } from "@dashboard/appearance/customPages/CustomPages.context";
import { CustomPagesAPI } from "@dashboard/appearance/customPages/CustomPagesApi";
import { IApiError } from "@library/@types/api/core";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

export function useCustomPagesQuery(params?: { status?: CustomPagesAPI.Status }) {
    const { status } = params || {};
    const query = useQuery({
        queryKey: ["customPages", status],
        queryFn: async () => {
            return await CustomPagesAPI.get(status);
        },
    });

    return query;
}

export function useCustomPagesCreate() {
    const queryClient = useQueryClient();
    return useMutation<CustomPagesAPI.Page, IApiError, CustomPagesAPI.CreateParams>({
        mutationFn: async (params: CustomPagesAPI.CreateParams) => {
            return await CustomPagesAPI.post(params);
        },
        mutationKey: ["customPages"],
        onSuccess: ({ customPageID }) => {
            void queryClient.invalidateQueries(["customPages"]);
        },
    });
}

export function useCustomPagesMutation() {
    const queryClient = useQueryClient();
    return useMutation<any, IApiError, any>({
        mutationFn: async (
            params: Partial<CustomPagesAPI.Page> & { customPageID: CustomPagesAPI.Page["customPageID"] },
        ) => {
            const { customPageID, ...rest } = params;
            return await CustomPagesAPI.patch(customPageID, rest);
        },
        mutationKey: ["customPages"],
        onSuccess: () => {
            void queryClient.invalidateQueries(["customPages"]);
        },
    });
}

export function useCustomPagesDelete() {
    const queryClient = useQueryClient();
    const { setPageToDelete } = useCustomPageContext();

    return useMutation({
        mutationFn: async (params: { customPageID: CustomPagesAPI.Page["customPageID"] }) => {
            return await CustomPagesAPI.delete(params.customPageID);
        },
        mutationKey: ["customPages"],
        onSuccess: () => {
            void queryClient.invalidateQueries(["customPages"]);
            setPageToDelete(null);
        },
    });
}
