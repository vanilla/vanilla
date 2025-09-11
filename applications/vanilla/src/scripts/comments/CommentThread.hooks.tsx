/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { CommentThreadSortOption } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IApiError } from "@library/@types/api/core";
import { scrollToElement } from "@library/content/hashScrolling";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import FlexSpacer from "@library/layout/FlexSpacer";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryClient, QueryKey, useQuery } from "@tanstack/react-query";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import { CommentThreadSort } from "@vanilla/addon-vanilla/comments/CommentThreadSort";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { useRef } from "react";

export function useDiscussionQuery(
    discussionID: IDiscussion["discussionID"],
    apiParams?: DiscussionsApi.GetParams,
    discussion?: IDiscussion,
) {
    const queryClient = useQueryClient();
    const queryKey: QueryKey = ["discussion", { discussionID, ...apiParams }];

    const query = useQuery<IDiscussion, IApiError>({
        queryFn: () => DiscussionsApi.get(discussionID, apiParams ?? {}),
        keepPreviousData: true,
        queryKey: queryKey,
        initialData: discussion,
    });

    return {
        query,
        invalidate: async function () {
            await queryClient.invalidateQueries({ queryKey: ["discussion", { discussionID }] });
        },
    };
}

export function useCommentThreadPager(
    pagingData: IWithPaging<any> | undefined,
    parentBaseUrl: string,
    defaultSort: CommentThreadSortOption,
    sort: CommentThreadSortOption | undefined,
    page: number,
    onSortChange: (sort: CommentThreadSortOption) => void,
    onPageChange: (page: number) => void,
) {
    const topPagerRef = useRef<HTMLDivElement>(null);
    useQueryStringSync({ sort: sort ?? defaultSort }, { sort: defaultSort });

    if (!pagingData) {
        return {
            topPager: <></>,
            bottomPager: <></>,
        };
    }
    const paging = pagingData.paging;

    const hasPager = paging.total! > paging.limit!;
    const pagerProps: INumberedPagerProps = {
        totalResults: paging.total,
        pageLimit: paging.limit,
        currentPage: page,
        onChange: (newPage) => {
            onPageChange(newPage);

            // Update the page URL.
            const discUrl = new URL(parentBaseUrl);
            let newPath = discUrl.pathname;
            if (newPage > 1) {
                newPath = newPath + "/p" + newPage;
            }
            // Preserve the existing query.
            newPath = newPath + window.location.search;
            // Don't use react router. We don't want to actually trigger a real re-render.
            window.history.pushState(null, "", newPath);
        },
    };

    const classes = commentThreadClasses();

    const topPager = (
        <div className={classes.sortPagerRow} ref={topPagerRef}>
            <CommentThreadSort
                currentSort={sort ?? defaultSort}
                selectSort={(newValue) => {
                    onSortChange(newValue);
                    onPageChange(1); // We go back to page 1.

                    // URL will sync automatically based on the queryParam sync.

                    // If the user is interacting with the sort in the top pager we don't really need to scroll anywhere.
                }}
            />
            <FlexSpacer />
            <NumberedPager {...pagerProps} showNextButton={false} className={classes.topPager} />
        </div>
    );

    const bottomPager = (
        <>
            {hasPager && (
                <NumberedPager
                    {...pagerProps}
                    onChange={(newPage) => {
                        pagerProps.onChange!(newPage);
                        // Now scroll to the top of the updated comment list.
                        if (topPagerRef.current) {
                            scrollToElement(topPagerRef.current);
                        }
                    }}
                />
            )}
        </>
    );

    return {
        topPager,
        bottomPager,
    };
}
