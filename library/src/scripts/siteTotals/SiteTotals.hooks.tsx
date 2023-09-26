import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useQuery } from "@tanstack/react-query";

export interface ISiteTotalsCount {
    counts: {
        user: {
            count: number;
            isCalculating: boolean;
            isFiltered: boolean;
        };
        discussion: {
            count: number;
            isCalculating: boolean;
            isFiltered: boolean;
        };
        post: {
            count: number;
            isCalculating: boolean;
            isFiltered: boolean;
        };
        comment: {
            count: number;
            isCalculating: boolean;
            isFiltered: boolean;
        };
        category: {
            count: number;
            isCalculating: boolean;
            isFiltered: boolean;
        };
    };
}

export interface IGetSiteTotalsCountResponse {
    userCount?: number;
    discussionCount?: number;
    commentCount?: number;
    postCount?: number;
    categoryCount?: number;
}

export enum SiteTotalsCountOption {
    USER = "user",
    DISCUSSION = "discussion",
    POST = "post",
    COMMENT = "comment",
    CATEGORY = "category",
    ALL = "all",
}

export async function getSiteTotalsCount(countOptions: SiteTotalsCountOption[]) {
    const { data } = await apiv2.get<ISiteTotalsCount>("/site-totals", {
        params: {
            counts: countOptions,
        },
    });

    const counts = countOptions[0] === SiteTotalsCountOption.ALL ? Object.keys(data.counts) : countOptions;

    const response = {};
    counts.forEach((option) => {
        response[`${option}Count`] = data.counts[option].count;
    });

    return response;
}

export function useGetSiteTotalsCount(countOptions: SiteTotalsCountOption[]) {
    const { refetch, isLoading, error, isSuccess, isFetching, data } = useQuery<
        any,
        IError,
        IGetSiteTotalsCountResponse
    >({
        queryFn: async () => getSiteTotalsCount(countOptions),
        queryKey: ["siteTotals_Count", countOptions],
        refetchOnMount: "always",
    });

    return { refetch, isLoading, error, isSuccess, isFetching, data };
}
