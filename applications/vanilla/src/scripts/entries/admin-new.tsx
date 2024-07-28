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
import { RouterRegistry } from "@library/Router.registry";
import { getCommunityManagementRoutes } from "@dashboard/moderation/CommunityManagementRoutes";
import { SuggestedAnswersPreview } from "@library/suggestedAnswers/SuggestedAnswers.preview";
import { getMeta } from "@library/utility/appUtils";

const suggestionsEnabled = getMeta("answerSuggestionsEnabled", false);

registerWidgetOverviews({
    TagWidget: TagWidgetPreview,
    DiscussionCommentsAsset: DiscussionCommentsAssetPreview,
    DiscussionAttachmentsAsset: DiscussionAttachmentsAssetPreview,
    DiscussionOriginalPostAsset: DiscussionOriginalPostAssetPreview,
    DiscussionCommentEditorAsset: DiscussionCommentEditorAsset,
    DiscussionTagAsset: DiscussionTagAssetPreview,
    DiscussionSuggestionsAsset: suggestionsEnabled ? SuggestedAnswersPreview : () => null,
});

RouterRegistry.addRoutes(getCommunityManagementRoutes());
