/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { ITag, ITagsStateStoreState } from "@library/features/tags/TagsReducer";
import { useEffect } from "react";
import { useTagsActions } from "@library/features/tags/TagsAction";
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";

export function useTagSearch(search: string): ILoadable<ITag[]> {
    search = search.trim();
    const { getTags } = useTagsActions();
    const tagsByName = useSelector((state: ITagsStateStoreState) => state.tags?.tagsByName[search]);

    const { status = LoadStatus.PENDING, data = {} } = tagsByName || {};

    useEffect(() => {
        if (status && status === LoadStatus.PENDING && search) {
            getTags({ name: search });
        }
    }, [search, status, getTags]);

    return tagsByName;
}

export interface IGetTagsParams {
    query?: string;
    limit?: number;
    page?: number;
    sort?: string;
    excludeNoCountDiscussion?: boolean;
    parentID?: number[];
    type?: string[];
    tagID?: number[]; // the api does not accept this yet, but will in the future
}

/**
 * Get list of tags
 */
export function useTagList(query: IGetTagsParams, shouldFetch: boolean = true) {
    const { isSuccess, data, isLoading } = useQuery<any, IApiError, ITag[]>({
        queryFn: async () => {
            const response = await apiv2.get("/tags", { params: query });
            return response.data;
        },
        queryKey: ["tags", query],
        enabled: shouldFetch,
    });

    return { isSuccess, data, isLoading };
}
