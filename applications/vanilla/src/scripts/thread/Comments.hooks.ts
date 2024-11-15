/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IApiError } from "@library/@types/api/core";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { QueryKey, useQuery, useQueryClient } from "@tanstack/react-query";
import { IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";

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

export function useCommentThreadQuery(
    apiParams: CommentsApi.IndexThreadParams,
    commentsData?: IWithPaging<IThreadResponse>,
) {
    return useQuery<IWithPaging<IThreadResponse>, IApiError>({
        queryKey: ["commentThread", apiParams],
        queryFn: () => {
            return CommentsApi.threadIndex(apiParams);
        },
        initialData: commentsData,
        keepPreviousData: true,
    });
}
