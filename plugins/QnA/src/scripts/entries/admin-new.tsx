/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import AnswerThreadAssetPreview from "@QnA/asset/AnswerThreadAsset.preview";

registerWidgetOverviews({
    AnswerThreadAsset: AnswerThreadAssetPreview,
});
