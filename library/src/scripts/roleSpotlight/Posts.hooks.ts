/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import SimplePagerModel, { IWithPaging } from "@library/navigation/SimplePagerModel";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Post, GetPostsRequestBody } from "@library/roleSpotlight/Posts.types";

export function usePostListQuery(params: GetPostsRequestBody, initialData?: IWithPaging<Post[]>) {
    const queryKey = ["getPosts", params];

    const query = useInfiniteQuery<IWithPaging<Post[]>, IApiError>({
        queryKey,
        queryFn: async ({ pageParam: page }) => {
            const response = await apiv2.get<Post[]>("/posts", { params: { ...params, page: page ?? params.page } });
            const { data, headers } = response;
            return {
                data,
                paging: SimplePagerModel.parseHeaders(headers),
            };
        },
        getNextPageParam: (lastPage) => {
            if (lastPage.paging.next) {
                return lastPage.paging.next;
            }
            if (lastPage.paging.currentPage && lastPage.paging.nextURL) {
                return lastPage.paging.currentPage + 1;
            }
        },
        keepPreviousData: true,
        initialData: () => {
            return initialData
                ? {
                      pageParams: [
                          {
                              next: initialData?.paging?.next
                                  ? initialData?.paging?.next + 1
                                  : initialData.paging.currentPage && initialData.paging.nextURL
                                  ? initialData.paging.currentPage + 1
                                  : undefined,
                          },
                      ],
                      pages: initialData ? [initialData] : [],
                  }
                : undefined;
        },
    });

    return query;
}
