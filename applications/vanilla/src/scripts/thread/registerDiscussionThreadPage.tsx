/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { registerLayoutPage } from "@library/features/Layout/LayoutPage";
import { getSiteSection } from "@library/utility/appUtils";
import { registerLoadableWidgets } from "@library/utility/componentRegistry";
import { DiscussionThreadPaginationContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadPaginationContext";
import { IDiscussion } from "@dashboard/@types/api/discussion";

interface IDiscussionThreadPageParams {
    id: IDiscussion["discussionID"];
    page: string;
}

export function registerDiscussionThreadPage() {
    registerLayoutPage<IDiscussionThreadPageParams>(
        ["/discussion/comment/:id(\\d+)"],
        (params) => {
            return {
                layoutViewType: "discussionThread",
                recordType: "comment",
                recordID: params.match.params.id,
                params: {
                    siteSectionID: getSiteSection().sectionID,
                    locale: getSiteSection().contentLocale,
                    commentID: params.match.params.id,
                },
            };
        },
        (layoutQuery, page) => {
            return (
                <DiscussionThreadPaginationContextProvider initialPage={layoutQuery.params.page}>
                    {page}
                </DiscussionThreadPaginationContextProvider>
            );
        },
    );

    registerLayoutPage<IDiscussionThreadPageParams>(
        ["/discussion/:id(\\d+)(/[^/]+)?/p:page(\\d+)", "/discussion/:id(\\d+)(/[^/]+)?"],
        (params) => {
            return {
                layoutViewType: "discussionThread",
                recordType: "discussion",
                recordID: params.match.params.id,
                params: {
                    siteSectionID: getSiteSection().sectionID,
                    locale: getSiteSection().contentLocale,
                    discussionID: params.match.params.id,
                    page: (params.match.params.page ?? 1).toString(),
                },
            };
        },
        (layoutQuery, page) => {
            return (
                <DiscussionThreadPaginationContextProvider initialPage={layoutQuery.params.page}>
                    {page}
                </DiscussionThreadPaginationContextProvider>
            );
        },
    );

    registerLoadableWidgets({
        DiscussionOriginalPostAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionOriginalPostAsset" */ "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset"
            ),
        DiscussionCommentsAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionCommentsAsset" */ "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset"
            ),
        DiscussionCommentEditorAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionCommentEditorAsset" */ "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset"
            ),
        DiscussionTagAsset: () =>
            import(
                /* webpackChunkName: "widgets/DiscussionTagAsset" */ "@vanilla/addon-vanilla/thread/DiscussionTagAsset"
            ),
    });
}
