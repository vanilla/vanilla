/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";

export interface IGetCategoryListParams {
    expand?: string;
    outputFormat?: "flat" | "tree";
    maxDepth?: number;
    limit?: number;
    followed?: boolean;
    categoryID?: number[];
    parentCategoryID?: number;
    siteSectionID?: number | string;
}

export function useCategoryList(queryParams: IGetCategoryListParams, shouldFetch: boolean = true) {
    const { refetch, isLoading, error, isSuccess, isFetching, data } = useQuery<any, IApiError, ICategory[]>({
        queryFn: async () => {
            const response = await apiv2.get("/categories", {
                params: {
                    ...queryParams,
                },
            });
            return response.data;
        },
        queryKey: ["categoryList", { ...queryParams }],
        enabled: shouldFetch,
    });

    return { refetch, error, isLoading, isSuccess, isFetching, data };
}
