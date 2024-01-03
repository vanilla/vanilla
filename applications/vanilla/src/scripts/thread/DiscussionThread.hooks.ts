/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IApiError } from "@library/@types/api/core";
import { useQueryClient, QueryKey, useQuery } from "@tanstack/react-query";
import { DiscussionsApi } from "./DiscussionsApi";

export function useDiscussionQuery(discussionID: IDiscussion["discussionID"], discussion?: IDiscussion) {
    const queryClient = useQueryClient();

    const queryKey: QueryKey = ["discussion", { discussionID }];

    const query = useQuery<IDiscussion, IApiError>({
        queryFn: () => DiscussionsApi.get({ discussionID }),
        keepPreviousData: true,
        queryKey: queryKey,
        initialData: discussion,
    });

    return {
        query,
        invalidate: async function invalidateDiscussionQuery() {
            await queryClient.invalidateQueries(queryKey);
        },
    };
}
