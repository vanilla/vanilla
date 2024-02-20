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
