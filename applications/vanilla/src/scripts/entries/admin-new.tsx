/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { registerWidgetOverviews } from "@dashboard/layout/overview/LayoutOverview";
import { TagWidgetPreview } from "@vanilla/addon-vanilla/tag/TagWidget.preview";

registerWidgetOverviews({
    TagWidget: TagWidgetPreview,
});
