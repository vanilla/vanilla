/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import { useQueryClient, QueryKey, useQuery } from "@tanstack/react-query";
import CommentsApi from "@vanilla/addon-vanilla/thread/CommentsApi";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IComment } from "@dashboard/@types/api/comment";
import apiv2 from "@library/apiv2";
import { IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { RecordID } from "@vanilla/utils";

export function useCommentListQuery(apiParams: CommentsApi.IndexParams, comments?: IWithPaging<IComment[]>) {
    const queryClient = useQueryClient();

    const queryKey: QueryKey = ["commentList", apiParams];

    const query = useQuery<IWithPaging<IComment[]>, IApiError>({
        queryFn: () => CommentsApi.index(apiParams),
        keepPreviousData: true,
        queryKey: queryKey,
        initialData: comments,
    });

    return {
        query,
        invalidate: async function () {
            await queryClient.invalidateQueries({
                queryKey: ["commentList", { discussionID: apiParams.discussionID }],
            });
        },
    };
}

interface CommentThreadParams {
    parentRecordType: "discussion" | "escalation";
    parentRecordID: RecordID;
    page: number;
    limit: number;
    sort: "dateInserted";
    expand: string;
}

export function useCommentThreadQuery(params?: Partial<CommentThreadParams>) {
    return useQuery<IThreadResponse, IApiError>({
        queryFn: async () => {
            const response = await apiv2.get(`/comments/thread`, {
                params: {
                    ...params,
                },
            });
            return response.data;
        },
        queryKey: ["commentThread", params],
    });
}
