/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import { GetPostsRequestBody, Post } from "@library/roleSpotlight/Posts.types";
import RoleSpotlightWidget from "@library/roleSpotlight/RoleSpotlightWidget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import { ComponentProps } from "react";

interface IProps extends Omit<ComponentProps<typeof RoleSpotlightWidget>, "postsApiParams" | "posts"> {
    titleType: string;
    descriptionType: string;

    apiParams?: {
        limit: string;
        roleID: string;
        includeComments?: boolean;
        showLoadMore?: boolean;
        sortExcludingComments?: string;
        sortIncludingComments?: string;
    };
}

const mockQueryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

const fakeDiscussions = DiscussionFixture.fakeDiscussions;
const fakeComments = CommentFixture.randomComments.map((body, index) => ({
    ...CommentFixture.comment({
        body: blessStringAsSanitizedHtml(body),
        commentID: index,
    }),
}));

const mockUser = UserFixture.createMockUser({ userID: 1 });
const mockPosts = Array.from({ length: 20 })
    .map((_, index) => {
        let discussion: Post = {
            ...fakeDiscussions[index % fakeDiscussions.length],
            discussionID: fakeDiscussions.length + index,
            insertUser: mockUser,
            updateUser: mockUser,
        };
        let comment: Post = fakeComments[index % fakeComments.length];
        comment = {
            ...comment,
            commentID: fakeComments.length + index,
            name: `Re: ${discussion.name}`,
            insertUser: mockUser,
            excerpt: comment.body,
        };
        return [discussion, comment];
    })
    .flat();

export default function RoleSpotlightWidgetPreview(props: IProps) {
    let title: IWidgetCommonProps["title"] | undefined;
    let description: IWidgetCommonProps["description"] | undefined;

    if (props.titleType !== "none") {
        title = props.title ?? "Title";
    }

    if (props.descriptionType !== "none") {
        description = props.description ?? "Description";
    }

    const { subtitle } = props;

    const limit = props.apiParams?.limit ? parseInt(`${props.apiParams?.limit ?? 10}`) : 10;

    const showLoadMore = props.apiParams?.showLoadMore ?? false;
    const includeComments = !!props.apiParams?.includeComments;

    return (
        <QueryClientProvider client={mockQueryClient}>
            <RoleSpotlightWidget
                {...{
                    ...props,
                    title,
                    description,
                    subtitle,

                    showLoadMore,
                    postsApiParams: {
                        page: 1,
                        limit,
                        roleIDs: props.apiParams?.roleID,
                        includeComments,
                        sort: props.apiParams?.includeComments
                            ? props.apiParams?.sortIncludingComments ?? "-commentDate"
                            : props.apiParams?.sortExcludingComments ?? "-dateLastComment",
                    } as GetPostsRequestBody,
                    posts: {
                        data: mockPosts
                            .filter((post) => {
                                return !!post && (includeComments ? true : !("commentID" in post));
                            })
                            .slice(0, limit),
                        paging: {
                            currentPage: 1,
                            next: 2,
                        },
                    },
                }}
            />
        </QueryClientProvider>
    );
}
