/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import React from "react";

interface IProps
    extends Omit<
        React.ComponentProps<typeof DiscussionOriginalPostAsset>,
        "commentsPreload" | "categoryID" | "discussion"
    > {}

const discussion = LayoutEditorPreviewData.discussion();

export function DiscussionOriginalPostAssetPreview(props: IProps) {
    return (
        <Widget>
            <DiscussionOriginalPostAsset
                {...props}
                category={LayoutEditorPreviewData.discussion().category!}
                discussion={discussion}
            />
        </Widget>
    );
}
