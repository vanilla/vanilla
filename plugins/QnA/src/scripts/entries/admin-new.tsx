/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import TabbedCommentListAssetPreview from "@QnA/asset/TabbedCommentListAsset.preview";

registerWidgetOverviews({
    TabbedCommentListAsset: TabbedCommentListAssetPreview,
});
