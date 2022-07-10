/*
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { addHomepageRouteOption } from "@dashboard/appearance/pages/HomepageLegacyLayoutsPage";
import BestOfPreviewThumbnail from "@Reactions/previews/BestOfPreviewThumbnail";

addHomepageRouteOption({
    label: "Best Of",
    value: "bestof",
    thumbnailComponent: BestOfPreviewThumbnail,
});
