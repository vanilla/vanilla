/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import { getCommunityManagementRoutes } from "@dashboard/moderation/CommunityManagementRoutes";
import { RouterRegistry } from "@library/Router.registry";
import { SuggestedAnswersAssetPreview } from "@library/suggestedAnswers/SuggestedAnswersAsset.preview";
import { CreateCommentAssetPreview } from "@vanilla/addon-vanilla/comments/CreateCommentAsset.preview";
import { CommentThreadAssetPreview } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.preview";
import CreatePostAssetPreview from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.preview";
import { OriginalPostAssetPreview } from "@vanilla/addon-vanilla/posts/OriginalPostAsset.preview";
import { PostAttachmentsAssetPreview } from "@vanilla/addon-vanilla/posts/PostAttachmentsAsset.preview";
import { PostMetaAssetPreview } from "@vanilla/addon-vanilla/posts/PostMetaAsset.preview";
import { PostTagsAssetPreview } from "@vanilla/addon-vanilla/posts/PostTagsAsset.preview";
import { TagWidgetPreview } from "@vanilla/addon-vanilla/tag/TagWidget.preview";

registerWidgetOverviews({
    TagWidget: TagWidgetPreview,
    CommentThreadAsset: CommentThreadAssetPreview,
    PostAttachmentsAsset: PostAttachmentsAssetPreview,
    OriginalPostAsset: OriginalPostAssetPreview,
    CreateCommentAsset: CreateCommentAssetPreview,
    PostTagsAsset: PostTagsAssetPreview,
    PostMetaAsset: PostMetaAssetPreview,
    SuggestedAnswersAsset: SuggestedAnswersAssetPreview,
    CreatePostFormAsset: CreatePostAssetPreview,
});

RouterRegistry.addRoutes(getCommunityManagementRoutes());
