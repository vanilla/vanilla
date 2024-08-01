/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { Widget } from "@library/layout/Widget";
import DiscussionCommentEditorAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import { discussionCommentEditorClasses } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset.classes";

const classes = discussionCommentEditorClasses();

export function DiscussionCommentEditorAssetPreview(props: React.ComponentProps<typeof DiscussionCommentEditorAsset>) {
    return (
        <Widget className={classes.previewDisablePointerEvents}>
            <DiscussionCommentEditorAsset discussionID={9999} categoryID={1} isPreview={true} />
        </Widget>
    );
}
