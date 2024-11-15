/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { registerLayoutPage } from "@library/features/Layout/LayoutPage.registry";
import { getMeta, getSiteSection } from "@library/utility/appUtils";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { DiscussionThreadPaginationContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadPaginationContext";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IComment } from "@dashboard/@types/api/comment";
import qs from "qs";

type IDiscussionThreadPageParams =
    | {
          discussionID: IDiscussion["discussionID"];
          page: string;
      }
    | {
          commentID: IComment["commentID"];
          page: string;
      };

export function registerDiscussionThreadPage() {
    registerLayoutPage<IDiscussionThreadPageParams>(
        [
            "/discussion/:discussionID(\\d+)(/[^/]+)?/p:page(\\d+)",
            "/discussion/:discussionID(\\d+)(/[^/]+)?",
            "/discussion/comment/:commentID(\\d+)",
        ],
        (params) => {
            const { location } = params;
            const urlQuery = qs.parse(location.search.substring(1));

            if ("commentID" in params.match.params) {
                return {
                    layoutViewType: "discussionThread",
                    recordType: "comment",
                    recordID: params.match.params.commentID,
                    params: {
                        siteSectionID: getSiteSection().sectionID,
                        locale: getSiteSection().contentLocale,
                        commentID: params.match.params.commentID,
                        sort: urlQuery.sort ?? undefined,
                    },
                };
            } else {
                return {
                    layoutViewType: "discussionThread",
                    recordType: "discussion",
                    recordID: params.match.params.discussionID,
                    params: {
                        siteSectionID: getSiteSection().sectionID,
                        locale: getSiteSection().contentLocale,
                        discussionID: params.match.params.discussionID,
                        page: (params.match.params.page ?? 1).toString(),
                        sort: urlQuery.sort ?? undefined,
                    },
                };
            }
        },
        (layoutQuery, page) => {
            return (
                <DiscussionThreadPaginationContextProvider initialPage={layoutQuery.params.page}>
                    {page}
                </DiscussionThreadPaginationContextProvider>
            );
        },
    );

    const suggestionsEnabled = getMeta("answerSuggestionsEnabled", false);

    registerLoadableWidgets({
        DiscussionOriginalPostAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionOriginalPostAsset" */ "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset"
            ),
        DiscussionCommentsAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionCommentsAsset" */ "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset"
            ),
        DiscussionAttachmentsAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionAttachmentsAsset" */ "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset"
            ),
        DiscussionCommentEditorAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionCommentEditorAsset" */ "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset"
            ),
        DiscussionTagAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionTagAsset" */ "@vanilla/addon-vanilla/thread/DiscussionTagAsset"
            ),
        DiscussionSuggestionsAsset: suggestionsEnabled
            ? () =>
                  import(
                      /* webpackChunkName: "widgets/DiscussionSuggestionsAsset" */ "@library/suggestedAnswers/SuggestedAnswers"
                  )
            : () => Promise.resolve({ default: () => null }),
    });
}
