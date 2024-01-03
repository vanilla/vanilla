/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import React from "react";

interface IProps
    extends Omit<
        React.ComponentProps<typeof DiscussionCommentsAsset>,
        "commentsPreload" | "categoryID" | "discussion"
    > {}

const comments = LayoutEditorPreviewData.comments(5);

export function DiscussionCommentsAssetPreview(props: IProps) {
    return (
        <Widget>
            <DiscussionCommentsAsset
                {...props}
                commentsPreload={{
                    data: comments,
                    paging: LayoutEditorPreviewData.paging(props.apiParams?.limit ?? 30),
                }}
                apiParams={{ discussionID: "fake", limit: 30, page: 1 }}
                discussion={{ ...LayoutEditorPreviewData.discussion() }}
            />
        </Widget>
    );
}
