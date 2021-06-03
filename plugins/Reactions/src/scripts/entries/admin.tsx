/*
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BestOfPreviewThumbnail from "@Reactions/previews/BestOfPreviewThumbnail";
import { addHomepageRouteOption } from "@dashboard/layout/pages/LayoutPage";

addHomepageRouteOption({
    label: "Best Of",
    value: "bestof",
    thumbnailComponent: BestOfPreviewThumbnail,
});
