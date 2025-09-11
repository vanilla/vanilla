/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { MetaLink } from "@library/metas/Metas";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { simplePagerClasses } from "@library/navigation/simplePagerStyles";
import { IResult } from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { usePostListQuery } from "@library/roleSpotlight//Posts.hooks";
import { GetPostsRequestBody, Post } from "@library/roleSpotlight/Posts.types";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

function isDiscussion(post: Post): post is IDiscussion {
    return "discussionID" in post && !("commentID" in post);
}

interface IProps extends IWidgetCommonProps {
    containerOptions?: IHomeWidgetContainerOptions;

    posts: IWithPaging<Post[]>;
    postsApiParams?: GetPostsRequestBody;

    displayOptions?: {
        featuredImage?: boolean;
        fallbackImage?: string;
    };

    itemOptions?: {
        author?: boolean;
        category?: boolean;
        dateUpdated?: boolean;
        excerpt?: boolean;
        userTags?: boolean;
    };

    showLoadMore?: boolean;
}

export default function RoleSpotlightWidget(props: IProps) {
    const { containerOptions, title, subtitle, description, showLoadMore = false } = props;

    const { posts: preloadedPosts, postsApiParams } = props;

    const query = usePostListQuery(
        {
            page: 1,
            limit: 10,
            ...postsApiParams,
        },
        preloadedPosts,
    );
    const { data: postsData, fetchNextPage, hasNextPage, isFetchingNextPage } = query;

    const {
        displayOptions = {
            featuredImage: false,
        },
    } = props;

    const { featuredImage, fallbackImage } = displayOptions;

    const {
        itemOptions = {
            author: false,
            category: false,
            dateUpdated: false,
            excerpt: false,
            userTags: false,
        },
    } = props;

    const { author, category, dateUpdated, excerpt, userTags } = itemOptions;

    function mapPostToResult(post: Post): IResult {
        const postIsDiscussion = isDiscussion(post);

        const result: IResult = {
            name: post.name,
            url: post.url,
            excerpt: excerpt ? post.excerpt : undefined,
            icon: <Icon icon={"search-discussions"} />,
            image: featuredImage ? (postIsDiscussion ? post.image?.url : undefined) : undefined,
            featuredImage: {
                display: !!featuredImage,
                fallbackImage,
            },
            meta: (
                <ResultMeta
                    type={postIsDiscussion ? "discussion" : "comment"}
                    updateUser={author ? post.insertUser : undefined}
                    dateUpdated={dateUpdated ? post.dateUpdated ?? post.dateInserted : undefined}
                    tags={postIsDiscussion && userTags ? post.tags : undefined}
                    extra={
                        category && !!post.category ? (
                            <MetaLink to={post.category.url}> {post.category.name} </MetaLink>
                        ) : undefined
                    }
                />
            ),
        };

        return result;
    }

    const flattenedPosts = postsData?.pages?.flatMap((page) => page.data) ?? [];
    const results = flattenedPosts.map(mapPostToResult);

    const pagerClasses = simplePagerClasses();

    return (
        <LayoutWidget>
            <HomeWidgetContainer options={containerOptions} title={title} subtitle={subtitle} description={description}>
                <ResultList results={results} emptyMessage={t("No posts found.")} />
                {showLoadMore && hasNextPage && (
                    <div className={pagerClasses.root}>
                        <Button
                            className={cx(pagerClasses.button, "isSingle")}
                            disabled={isFetchingNextPage}
                            onClick={async () => await fetchNextPage()}
                        >
                            {t("Load More")}
                        </Button>
                    </div>
                )}
            </HomeWidgetContainer>
        </LayoutWidget>
    );
}
