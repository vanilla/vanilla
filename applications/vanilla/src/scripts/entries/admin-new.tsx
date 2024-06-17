/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import { TagWidgetPreview } from "@vanilla/addon-vanilla/tag/TagWidget.preview";
import { DiscussionCommentEditorAsset } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import { DiscussionCommentsAssetPreview } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.preview";
import { DiscussionAttachmentsAssetPreview } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset.preview";
import { DiscussionOriginalPostAssetPreview } from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset.preview";
import { DiscussionTagAssetPreview } from "@vanilla/addon-vanilla/thread/DiscussionTagAsset.preview";

registerWidgetOverviews({
    TagWidget: TagWidgetPreview,
    DiscussionCommentsAsset: DiscussionCommentsAssetPreview,
    DiscussionAttachmentsAsset: DiscussionAttachmentsAssetPreview,
    DiscussionOriginalPostAsset: DiscussionOriginalPostAssetPreview,
    DiscussionCommentEditorAsset: DiscussionCommentEditorAsset,
    DiscussionTagAsset: DiscussionTagAssetPreview,
});
