/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerLayoutPage } from "@library/features/Layout/LayoutPage.registry";
import { getMeta, getSiteSection } from "@library/utility/appUtils";
import { addComponent, registerLoadableWidgets } from "@library/utility/componentRegistry";
import { CommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { DraftContextProvider } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { getParamsFromPath } from "@vanilla/addon-vanilla/drafts/utils";
import { CreateCommentProvider } from "@vanilla/addon-vanilla/posts/CreateCommentContext";
import { ParentRecordContextProvider } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import { PostPageContextProvider } from "@vanilla/addon-vanilla/posts/PostPageContext";
import * as qs from "qs-esm";

const postPageEnabled = getMeta("featureFlags.customLayout.post.Enabled", false);
const postListEnabled = getMeta("featureFlags.customLayout.discussionList.Enabled", false);
const categoryListEnabled = getMeta("featureFlags.customLayout.categoryList.Enabled", false);

postListEnabled &&
    registerLayoutPage("/discussions", (routeParams) => {
        const { location } = routeParams;
        const urlQuery = qs.parse(location.search.substring(1));

        return {
            layoutViewType: "discussionList",
            recordType: "siteSection",
            recordID: getSiteSection().sectionID,
            params: {
                ...urlQuery,
            },
        };
    });

/**
 * Register Categories Page
 */
categoryListEnabled &&
    registerLayoutPage("/categories", (routeParams) => {
        const { location } = routeParams;
        const urlQuery = qs.parse(location.search.substring(1));
        return {
            layoutViewType: "categoryList",
            recordType: "siteSection",
            recordID: getSiteSection().sectionID,
            params: {
                ...urlQuery,
            },
        };
    });

interface ICategoryPageParams {
    id: number | string;
    page: string;
}

/**
 * Register Category Pages
 */
categoryListEnabled &&
    registerLayoutPage<ICategoryPageParams>(
        ["/categories/:id([^/]+)?/p:page(\\d+)", "/categories/:id([^/]+)?"],
        (params) => {
            const { location } = params;
            const urlQuery = qs.parse(location.search.substring(1));
            return {
                layoutViewType: "categoryList",
                recordType: "category",
                recordID: params.match.params.id,
                params: {
                    ...urlQuery,
                    categoryID: params.match.params.id,
                    page: (params.match.params.page ?? urlQuery.page ?? 1).toString(),
                },
            };
        },
    );

type IPostPageParams =
    | {
          discussionID: string;
          page: string;
      }
    | {
          commentID: string;
          page: string;
      };

postPageEnabled &&
    registerLayoutPage<IPostPageParams>(
        [
            "/discussion/:discussionID(\\d+)(/[^/]+)?/p:page(\\d+)",
            "/discussion/:discussionID(\\d+)(/[^/]+)?",
            "/discussion/comment/:commentID(\\d+)",
        ],
        (params) => {
            const { location } = params;
            const urlQuery = qs.parse(location.search.substring(1));
            const sort = urlQuery.sort ?? null;

            if ("commentID" in params.match.params) {
                const commentID = parseInt(params.match.params.commentID);
                return {
                    layoutViewType: "post",
                    recordType: "comment",
                    recordID: commentID,
                    params: {
                        commentID,
                        sort,
                    },
                };
            } else {
                const discussionID = parseInt(params.match.params.discussionID);
                const page = parseInt(params.match.params.page ?? 1);
                return {
                    layoutViewType: "post",
                    recordType: "discussion",
                    recordID: discussionID,
                    params: {
                        discussionID,
                        page,
                        sort,
                    },
                };
            }
        },
    );

const customCreatePostEnabled = getMeta("featureFlags.customLayout.createPost.Enabled", false);

if (customCreatePostEnabled) {
    // We will handle all the new post route here on the FE
    registerLayoutPage(["*/post/*"], (routeParams) => {
        const { location } = routeParams;
        const { pathname, search } = location;
        const parameters = getParamsFromPath(pathname, search);
        const urlQuery = qs.parse(location.search.substring(1));
        let layoutViewType = `createPost`;

        return {
            layoutViewType,
            recordType: "siteSection",
            recordID: getSiteSection().sectionID,
            params: {
                siteSectionID: getSiteSection().sectionID,
                locale: getSiteSection().contentLocale,
                ...parameters,
                ...urlQuery,
            },
        };
    });
}

addComponent("PostPageContextProvider", PostPageContextProvider);
addComponent("CommentThreadParentContext", CommentThreadParentContext);
addComponent("DraftContextProvider", DraftContextProvider);
addComponent("CreateCommentProvider", CreateCommentProvider);
addComponent("ParentRecordContextProvider", ParentRecordContextProvider);

registerLoadableWidgets({
    OriginalPostAsset: () => import("@vanilla/addon-vanilla/posts/OriginalPostAsset"),
    CommentThreadAsset: () => import("@vanilla/addon-vanilla/comments/CommentThreadAsset"),
    PostAttachmentsAsset: () => import("@vanilla/addon-vanilla/posts/PostAttachmentsAsset"),
    PostMetaAsset: () => import("@vanilla/addon-vanilla/posts/PostMetaAsset"),
    CreateCommentAsset: () => import("@vanilla/addon-vanilla/comments/CreateCommentAsset"),
    PostTagsAsset: () => import("@vanilla/addon-vanilla/posts/PostTagsAsset"),
    SuggestedAnswersAsset: () => import("@library/suggestedAnswers/SuggestedAnswersAsset"),
    CreatePostFormAsset: () => import("@vanilla/addon-vanilla/createPost/CreatePostFormAsset"),
});
