/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";

export interface IGetCategoryListParams {
    expand?: string;
    outputFormat?: "flat" | "tree";
    maxDepth?: number;
    limit?: number;
    followed?: boolean;
    categoryID?: number[];
    parentCategoryID?: number;
    siteSectionID?: number | string;
    page?: number;
    sort?: "categoryID" | "name" | "dateFollowed" | "-dateFollowed";
}

export interface IGetCategoryListResponse {
    result: ICategory[];
    pagination: ILinkPages;
}

export function useCategoryList(queryParams: IGetCategoryListParams, shouldFetch: boolean = true) {
    const { refetch, isLoading, error, isSuccess, isFetching, data } = useQuery<
        any,
        IApiError,
        IGetCategoryListResponse
    >({
        queryFn: async () => {
            const response = await apiv2.get("/categories", {
                params: {
                    ...queryParams,
                },
            });
            const pagination = SimplePagerModel.parseHeaders(response.headers);
            return { result: response.data, pagination: pagination };
        },
        keepPreviousData: true,
        queryKey: ["categoryList", { ...queryParams }],
        enabled: shouldFetch,
    });

    return { refetch, error, isLoading, isSuccess, isFetching, data };
}
