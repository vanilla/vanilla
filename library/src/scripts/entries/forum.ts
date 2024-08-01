/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { onPageViewWithContext } from "@library/analytics/AnalyticsData";
import { trackPageView, trackLink } from "@library/analytics/tracking";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { getMeta, setMeta } from "@library/utility/appUtils";

// Tracking page views
trackPageView();
setMeta("trackLegacyPageViews", true);

onPageViewWithContext((event: CustomEvent) => {
    trackPageView(window.location.href, event.detail);
});

if (getMeta("inputFormat.desktop")?.match(/rich2/i)) {
    supportsFrames(true);
}

trackLink();
